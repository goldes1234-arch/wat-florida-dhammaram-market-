<?php

namespace App\Services;

use App\Core\App;
use App\Models\Setting;

/**
 * Keeps an off-server copy of every backup in the temple's own Google Drive, so losing the web
 * host no longer loses the backups too. Plain OAuth2 + the Drive REST API over cURL (no SDK).
 *
 * Scope is `drive.file`: the app can only see files and folders it created itself, so a leaked
 * refresh token cannot read anything else in the Drive. The admin connects once from
 * /admin/backups; after that the daily /cron/backup uploads each new dump and prunes old copies.
 */
class GoogleDriveService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    private const API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_API = 'https://www.googleapis.com/upload/drive/v3/files';
    private const SCOPE = 'https://www.googleapis.com/auth/drive.file';
    public const FOLDER_NAME = 'Wat Florida Market - Database Backups';
    private const BACKUP_NAME_PATTERN = '/^temple_market_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/';

    /** @var callable|null replaces the real HTTP calls in tests: fn(string $method, string $url, array $headers, ?string $body): array{status:int, body:string, headers:array} */
    private static $transport = null;

    public static function setTransport(?callable $transport): void
    {
        self::$transport = $transport;
    }

    /** OAuth client id/secret are set in .env, and this is not a staging copy (staging must never write to the real Drive). */
    public static function isConfigured(): bool
    {
        return App::config('google.client_id') !== '' && App::config('google.client_secret') !== ''
            && App::config('app.env') !== 'staging';
    }

    public static function isConnected(): bool
    {
        return self::isConfigured() && !empty(Setting::get(true)['gdrive_refresh_token']);
    }

    public static function redirectUri(): string
    {
        return full_url('admin/backups/google/callback');
    }

    public static function authUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => App::config('google.client_id'),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent', // without this Google omits the refresh token on a repeat connect
            'state' => $state,
            'include_granted_scopes' => 'false',
        ]);
    }

    /** @return array{0: bool, 1: string} [ok, error or connected account] */
    public static function connect(string $code): array
    {
        $res = self::send('POST', self::TOKEN_URL, ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
            'code' => $code,
            'client_id' => App::config('google.client_id'),
            'client_secret' => App::config('google.client_secret'),
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]));
        $data = json_decode($res['body'], true) ?: [];
        if ($res['status'] !== 200 || empty($data['refresh_token'])) {
            return [false, self::describeError($data, 'no refresh token returned — remove the app at myaccount.google.com/permissions and connect again')];
        }

        Setting::update([
            'gdrive_refresh_token' => $data['refresh_token'],
            'gdrive_folder_id' => null,
            'gdrive_last_error' => null,
        ]);

        // Drive "about" works with drive.file and tells the admin which account they connected.
        $account = '';
        if (!empty($data['access_token'])) {
            $about = self::api('GET', self::API . '/about?fields=user(emailAddress)', $data['access_token']);
            $account = (string) ($about['json']['user']['emailAddress'] ?? '');
        }
        Setting::update(['gdrive_account' => $account ?: null]);

        return [true, $account];
    }

    public static function disconnect(): void
    {
        $token = Setting::get(true)['gdrive_refresh_token'] ?? '';
        if ($token !== '') {
            self::send('POST', self::REVOKE_URL, ['Content-Type: application/x-www-form-urlencoded'], http_build_query(['token' => $token]));
        }
        Setting::update([
            'gdrive_refresh_token' => null, 'gdrive_folder_id' => null, 'gdrive_account' => null,
            'gdrive_last_error' => null,
        ]);
    }

    /**
     * Uploads one backup file to the backups folder, then deletes the oldest copies beyond the
     * configured keep count. Never throws; the outcome is also stored for the admin page.
     *
     * @return array{0: bool, 1: string} [ok, message]
     */
    public static function uploadBackup(string $path, string $name): array
    {
        try {
            [$ok, $message] = self::doUpload($path, $name);
        } catch (\Throwable $e) {
            [$ok, $message] = [false, $e->getMessage()];
        }

        Setting::update($ok
            ? ['gdrive_last_upload_at' => date('Y-m-d H:i:s'), 'gdrive_last_error' => null]
            : ['gdrive_last_error' => mb_substr($message, 0, 250)]);

        return [$ok, $message];
    }

    private static function doUpload(string $path, string $name): array
    {
        if (!self::isConnected()) {
            return [false, 'Google Drive is not connected'];
        }
        if (!is_file($path)) {
            return [false, 'backup file not found'];
        }

        [$token, $error] = self::accessToken();
        if ($token === null) {
            return [false, $error];
        }

        $folderId = self::ensureFolder($token);
        if ($folderId === null) {
            return [false, 'could not create the Drive folder'];
        }

        $uploaded = self::putFile($token, $folderId, $path, $name);
        if (!$uploaded) {
            // The folder may have been deleted from the Drive by hand: recreate it once and retry.
            Setting::update(['gdrive_folder_id' => null]);
            $folderId = self::ensureFolder($token);
            $uploaded = $folderId !== null && self::putFile($token, $folderId, $path, $name);
        }
        if (!$uploaded) {
            return [false, 'Drive rejected the upload'];
        }

        self::prune($token, $folderId);
        return [true, $name];
    }

    /** @return array{0: ?string, 1: string} [access token, error] */
    private static function accessToken(): array
    {
        $res = self::send('POST', self::TOKEN_URL, ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
            'client_id' => App::config('google.client_id'),
            'client_secret' => App::config('google.client_secret'),
            'refresh_token' => Setting::get(true)['gdrive_refresh_token'],
            'grant_type' => 'refresh_token',
        ]));
        $data = json_decode($res['body'], true) ?: [];
        if ($res['status'] !== 200 || empty($data['access_token'])) {
            $hint = ($data['error'] ?? '') === 'invalid_grant' ? ' — access was revoked or expired, please reconnect' : '';
            return [null, self::describeError($data, 'token refresh failed') . $hint];
        }
        return [$data['access_token'], ''];
    }

    private static function ensureFolder(string $token): ?string
    {
        $stored = Setting::get(true)['gdrive_folder_id'] ?? null;
        if ($stored) {
            $check = self::api('GET', self::API . '/files/' . rawurlencode($stored) . '?fields=id,trashed', $token);
            if ($check['status'] === 200 && empty($check['json']['trashed'])) {
                return $stored;
            }
        }

        $created = self::api('POST', self::API . '/files?fields=id', $token, [
            'name' => self::FOLDER_NAME,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        $id = $created['json']['id'] ?? null;
        if ($created['status'] === 200 && $id) {
            Setting::update(['gdrive_folder_id' => $id]);
            return $id;
        }
        return null;
    }

    /** Resumable upload: start a session with the metadata, then send the bytes in one request. */
    private static function putFile(string $token, string $folderId, string $path, string $name): bool
    {
        $size = filesize($path);
        $start = self::send('POST', self::UPLOAD_API . '?uploadType=resumable&fields=id', [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=UTF-8',
            'X-Upload-Content-Type: application/gzip',
            'X-Upload-Content-Length: ' . $size,
        ], json_encode(['name' => $name, 'parents' => [$folderId]]));
        $location = $start['headers']['location'] ?? '';
        if ($start['status'] !== 200 || $location === '') {
            return false;
        }

        $put = self::send('PUT', $location, [
            'Content-Type: application/gzip',
        ], file_get_contents($path));
        return in_array($put['status'], [200, 201], true) && !empty((json_decode($put['body'], true) ?: [])['id']);
    }

    /** Keeps only the newest N backup files in the folder (names are checked so nothing else is ever deleted). */
    private static function prune(string $token, string $folderId): void
    {
        $keep = max(1, (int) App::config('google.keep', 30));
        $list = self::api('GET', self::API . '/files?' . http_build_query([
            'q' => "'" . $folderId . "' in parents and trashed = false",
            'orderBy' => 'createdTime desc',
            'pageSize' => 200,
            'fields' => 'files(id,name)',
        ]), $token);

        $files = array_values(array_filter(
            $list['json']['files'] ?? [],
            static fn (array $f) => preg_match(self::BACKUP_NAME_PATTERN, $f['name'] ?? '') === 1
        ));
        foreach (array_slice($files, $keep) as $old) {
            self::api('DELETE', self::API . '/files/' . rawurlencode($old['id']), $token);
        }
    }

    /** @return array{status:int, json:array} */
    private static function api(string $method, string $url, string $token, ?array $jsonBody = null): array
    {
        $headers = ['Authorization: Bearer ' . $token];
        if ($jsonBody !== null) {
            $headers[] = 'Content-Type: application/json; charset=UTF-8';
        }
        $res = self::send($method, $url, $headers, $jsonBody !== null ? json_encode($jsonBody) : null);
        return ['status' => $res['status'], 'json' => json_decode($res['body'], true) ?: []];
    }

    private static function describeError(array $data, string $fallback): string
    {
        $text = $data['error_description'] ?? (is_array($data['error'] ?? null) ? ($data['error']['message'] ?? '') : ($data['error'] ?? ''));
        return $text !== '' ? (string) $text : $fallback;
    }

    /** @return array{status:int, body:string, headers:array<string,string>} */
    private static function send(string $method, string $url, array $headers, ?string $body): array
    {
        if (self::$transport !== null) {
            return (self::$transport)($method, $url, $headers, $body);
        }

        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log('Google Drive request failed: ' . $curlError);
            return ['status' => 0, 'body' => '', 'headers' => []];
        }
        return ['status' => $status, 'body' => (string) $response, 'headers' => $responseHeaders];
    }
}
