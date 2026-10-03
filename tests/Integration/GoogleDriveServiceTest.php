<?php

namespace Tests\Integration;

use App\Core\App;
use App\Models\Setting;
use App\Services\GoogleDriveService as Drive;
use Tests\TestCase;

/** Drives the whole Drive flow against a scripted fake of Google's endpoints — no network. */
class GoogleDriveServiceTest extends TestCase
{
    private array $originalConfig;
    private array $originalSettings;
    private array $requests = [];
    private string $backupFile;
    private const NAME = 'temple_market_2026-10-04_020000.sql.gz';

    public function setUp(): void
    {
        $this->originalConfig = App::config();
        App::boot(array_replace_recursive($this->originalConfig, [
            'google' => ['client_id' => 'cid', 'client_secret' => 'csecret', 'keep' => 3],
            'app' => ['env' => 'production'],
        ]));

        $s = Setting::get(true);
        $this->originalSettings = [];
        foreach (['gdrive_refresh_token', 'gdrive_folder_id', 'gdrive_account', 'gdrive_last_upload_at', 'gdrive_last_error'] as $col) {
            $this->originalSettings[$col] = $s[$col] ?? null;
        }
        Setting::update(['gdrive_refresh_token' => null, 'gdrive_folder_id' => null, 'gdrive_account' => null, 'gdrive_last_upload_at' => null, 'gdrive_last_error' => null]);

        $this->backupFile = sys_get_temp_dir() . '/' . self::NAME;
        file_put_contents($this->backupFile, 'fake gzip bytes');
        $this->requests = [];
    }

    public function tearDown(): void
    {
        Drive::setTransport(null);
        App::boot($this->originalConfig);
        Setting::update($this->originalSettings);
        @unlink($this->backupFile);
    }

    /** Installs a fake Google: $routes maps "METHOD fragment-of-url" => response (array or callable). Anything else is a 404. */
    private function fakeGoogle(array $routes): void
    {
        Drive::setTransport(function (string $method, string $url, array $headers, ?string $body) use ($routes) {
            $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
            foreach ($routes as $key => $response) {
                [$m, $fragment] = explode(' ', $key, 2);
                if ($m === $method && str_contains($url, $fragment)) {
                    return is_callable($response) ? $response($url, $body) : $response;
                }
            }
            return ['status' => 404, 'body' => '{}', 'headers' => []];
        });
    }

    private static function json(array $data, int $status = 200, array $headers = []): array
    {
        return ['status' => $status, 'body' => json_encode($data), 'headers' => $headers];
    }

    private function connectedWithToken(): void
    {
        Setting::update(['gdrive_refresh_token' => 'REFRESH']);
    }

    public function testNotConfiguredOrOnStagingMeansDisabled(): void
    {
        $this->assertTrue(Drive::isConfigured());

        App::boot(array_replace_recursive(App::config(), ['app' => ['env' => 'staging']]));
        $this->assertFalse(Drive::isConfigured(), 'staging must never write to the real Drive');

        App::boot(array_replace_recursive(App::config(), ['app' => ['env' => 'production'], 'google' => ['client_id' => '']]));
        $this->assertFalse(Drive::isConfigured());
    }

    public function testAuthUrlAsksForOfflineDriveFileAccessWithTheState(): void
    {
        $url = Drive::authUrl('STATE123');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

        $this->assertSame('cid', $q['client_id']);
        $this->assertSame('STATE123', $q['state']);
        $this->assertSame('offline', $q['access_type']);
        $this->assertSame('consent', $q['prompt']);
        $this->assertSame('https://www.googleapis.com/auth/drive.file', $q['scope'], 'only files this app creates, never the whole Drive');
        $this->assertTrue(str_ends_with($q['redirect_uri'], '/admin/backups/google/callback'));
    }

    public function testConnectStoresTheRefreshTokenAndTheAccount(): void
    {
        $this->fakeGoogle([
            'POST oauth2.googleapis.com/token' => self::json(['access_token' => 'AT', 'refresh_token' => 'RT']),
            'GET /about' => self::json(['user' => ['emailAddress' => 'temple@example.org']]),
        ]);

        [$ok, $account] = Drive::connect('the-code');

        $this->assertTrue($ok);
        $this->assertSame('temple@example.org', $account);
        $s = Setting::get(true);
        $this->assertSame('RT', $s['gdrive_refresh_token']);
        $this->assertSame('temple@example.org', $s['gdrive_account']);
        $this->assertTrue(Drive::isConnected());
        $this->assertTrue(str_contains($this->requests[0]['body'], 'code=the-code'));
    }

    public function testConnectFailsClearlyWhenGoogleReturnsNoRefreshToken(): void
    {
        $this->fakeGoogle(['POST oauth2.googleapis.com/token' => self::json(['access_token' => 'AT'])]);

        [$ok, $message] = Drive::connect('code');

        $this->assertFalse($ok);
        $this->assertTrue(str_contains($message, 'refresh token'));
        $this->assertFalse(Drive::isConnected());
    }

    public function testUploadCreatesTheFolderUploadsTheFileAndRecordsSuccess(): void
    {
        $this->connectedWithToken();
        $this->fakeGoogle([
            'POST oauth2.googleapis.com/token' => self::json(['access_token' => 'AT']),
            'POST /drive/v3/files?fields=id' => self::json(['id' => 'FOLDER1']),
            'POST /upload/drive/v3/files' => ['status' => 200, 'body' => '{}', 'headers' => ['location' => 'https://upload.example/session1']],
            'PUT https://upload.example/session1' => self::json(['id' => 'FILE1']),
            'GET /drive/v3/files?q=' => self::json(['files' => [['id' => 'FILE1', 'name' => self::NAME]]]),
        ]);

        [$ok, $message] = Drive::uploadBackup($this->backupFile, self::NAME);

        $this->assertTrue($ok, $message);
        $s = Setting::get(true);
        $this->assertSame('FOLDER1', $s['gdrive_folder_id']);
        $this->assertNotNull($s['gdrive_last_upload_at']);
        $this->assertNull($s['gdrive_last_error']);

        $start = array_values(array_filter($this->requests, fn ($r) => str_contains($r['url'], 'uploadType=resumable')))[0];
        $meta = json_decode($start['body'], true);
        $this->assertSame(self::NAME, $meta['name']);
        $this->assertSame(['FOLDER1'], $meta['parents']);
        $put = array_values(array_filter($this->requests, fn ($r) => $r['method'] === 'PUT'))[0];
        $this->assertSame('fake gzip bytes', $put['body']);
    }

    public function testOnlyTheNewestCopiesAreKeptAndNothingElseIsEverDeleted(): void
    {
        $this->connectedWithToken();
        Setting::update(['gdrive_folder_id' => 'FOLDER1']);
        $files = [];
        foreach (['2026-10-04', '2026-10-03', '2026-10-02', '2026-10-01', '2026-09-30'] as $i => $d) {
            $files[] = ['id' => 'F' . $i, 'name' => 'temple_market_' . $d . '_020000.sql.gz'];
        }
        $files[] = ['id' => 'NOTES', 'name' => 'my-notes.txt'];
        $this->fakeGoogle([
            'POST oauth2.googleapis.com/token' => self::json(['access_token' => 'AT']),
            'GET /drive/v3/files/FOLDER1' => self::json(['id' => 'FOLDER1', 'trashed' => false]),
            'POST /upload/drive/v3/files' => ['status' => 200, 'body' => '{}', 'headers' => ['location' => 'https://upload.example/s']],
            'PUT https://upload.example/s' => self::json(['id' => 'NEW']),
            'GET /drive/v3/files?q=' => self::json(['files' => $files]),
            'DELETE /drive/v3/files/' => ['status' => 204, 'body' => '', 'headers' => []],
        ]);

        [$ok] = Drive::uploadBackup($this->backupFile, self::NAME);

        $this->assertTrue($ok);
        $deleted = array_map(
            fn ($r) => basename($r['url']),
            array_values(array_filter($this->requests, fn ($r) => $r['method'] === 'DELETE'))
        );
        $this->assertSame(['F3', 'F4'], $deleted, 'keep=3: the two oldest backups go, the notes file is never touched');
    }

    public function testAFolderDeletedFromDriveIsRecreated(): void
    {
        $this->connectedWithToken();
        Setting::update(['gdrive_folder_id' => 'GONE']);
        $this->fakeGoogle([
            'POST oauth2.googleapis.com/token' => self::json(['access_token' => 'AT']),
            'GET /drive/v3/files/GONE' => self::json(['error' => ['message' => 'not found']], 404),
            'POST /drive/v3/files?fields=id' => self::json(['id' => 'NEWFOLDER']),
            'POST /upload/drive/v3/files' => ['status' => 200, 'body' => '{}', 'headers' => ['location' => 'https://upload.example/s']],
            'PUT https://upload.example/s' => self::json(['id' => 'X']),
            'GET /drive/v3/files?q=' => self::json(['files' => []]),
        ]);

        [$ok] = Drive::uploadBackup($this->backupFile, self::NAME);

        $this->assertTrue($ok);
        $this->assertSame('NEWFOLDER', Setting::get(true)['gdrive_folder_id']);
    }

    public function testRevokedAccessIsReportedAndRemembered(): void
    {
        $this->connectedWithToken();
        $this->fakeGoogle(['POST oauth2.googleapis.com/token' => self::json(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400)]);

        [$ok, $message] = Drive::uploadBackup($this->backupFile, self::NAME);

        $this->assertFalse($ok);
        $this->assertTrue(str_contains($message, 'reconnect'));
        $this->assertTrue(str_contains((string) Setting::get(true)['gdrive_last_error'], 'reconnect'));
        $this->assertNull(Setting::get(true)['gdrive_last_upload_at'] ?? null);
    }

    public function testUploadWithoutAConnectionFailsWithoutCallingGoogle(): void
    {
        $this->fakeGoogle([]);

        [$ok] = Drive::uploadBackup($this->backupFile, self::NAME);

        $this->assertFalse($ok);
        $this->assertSame([], $this->requests);
    }

    public function testDisconnectRevokesAndClearsEverything(): void
    {
        Setting::update(['gdrive_refresh_token' => 'RT', 'gdrive_folder_id' => 'F', 'gdrive_account' => 'a@b.c']);
        $this->fakeGoogle(['POST oauth2.googleapis.com/revoke' => ['status' => 200, 'body' => '', 'headers' => []]]);

        Drive::disconnect();

        $s = Setting::get(true);
        $this->assertNull($s['gdrive_refresh_token']);
        $this->assertNull($s['gdrive_folder_id']);
        $this->assertNull($s['gdrive_account']);
        $this->assertTrue(str_contains($this->requests[0]['body'], 'token=RT'));
    }
}
