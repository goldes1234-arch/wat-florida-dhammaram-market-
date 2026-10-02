<?php

namespace App\Services;

use App\Core\App;

/**
 * Dumps the whole database to a gzip-compressed .sql.gz file via mysqldump
 * (shelling out — no PDO-based dump would capture triggers/routines as
 * faithfully), stored under storage/backups/ (denied by .htaccess, same as
 * the email log folder). Old backups beyond the retention count are pruned
 * automatically after each run.
 */
class BackupService
{
    private const FILENAME_PATTERN = '/^temple_market_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/';

    public static function dir(): string
    {
        return BASE_PATH . '/storage/backups';
    }

    /** @return array{0: bool, 1: string} [success, filename on success / error message on failure] */
    public static function create(): array
    {
        if (!self::execAvailable()) {
            return [false, 'PHP exec() is disabled on this server — automatic backups are unavailable here.'];
        }

        $mysqldump = self::resolveBinary('mysqldump', App::config('backup.mysqldump_path'));
        if (!$mysqldump) {
            return [false, 'mysqldump binary not found. Set MYSQLDUMP_PATH in .env to its absolute path.'];
        }

        $dir = self::dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return [false, 'Could not create storage/backups directory.'];
        }

        $config = App::config('db');
        $filename = 'temple_market_' . date('Y-m-d_His') . '.sql.gz';
        $sqlPath = $dir . '/' . uniqid('dump_', true) . '.sql';
        $gzPath = $dir . '/' . $filename;

        $tmpConfig = tempnam(sys_get_temp_dir(), 'tmdbcnf');
        $lines = ['[client]', 'user="' . self::escapeIni($config['username']) . '"'];
        if ($config['password'] !== '') {
            $lines[] = 'password="' . self::escapeIni($config['password']) . '"';
        }
        if (!empty($config['socket'])) {
            $lines[] = 'socket="' . self::escapeIni($config['socket']) . '"';
        } else {
            $lines[] = 'host="' . self::escapeIni($config['host']) . '"';
            $lines[] = 'port="' . self::escapeIni((string) $config['port']) . '"';
        }
        file_put_contents($tmpConfig, implode("\n", $lines) . "\n");
        chmod($tmpConfig, 0600);

        // --routines is deliberately omitted: this schema defines none, and on some MariaDB
        // installs (mismatched mysql.proc table version) requesting it makes mysqldump exit
        // non-zero even though the actual table/trigger dump above it succeeded fine.
        $cmd = sprintf(
            '%s --defaults-extra-file=%s --single-transaction --triggers %s > %s 2>&1',
            escapeshellcmd($mysqldump),
            escapeshellarg($tmpConfig),
            escapeshellarg($config['database']),
            escapeshellarg($sqlPath)
        );

        exec($cmd, $outputLines, $exitCode);
        unlink($tmpConfig);

        if ($exitCode !== 0 || !is_file($sqlPath) || filesize($sqlPath) === 0) {
            if (is_file($sqlPath)) {
                unlink($sqlPath);
            }
            return [false, 'mysqldump failed: ' . implode(' ', $outputLines)];
        }

        $ok = self::gzipFile($sqlPath, $gzPath);
        unlink($sqlPath);

        if (!$ok) {
            return [false, 'Backup ran but could not be compressed.'];
        }

        self::prune();

        return [true, $filename];
    }

    /** @return array<int, array{filename: string, size: int, created_at: int}> newest first */
    public static function list(): array
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) ?: [] as $name) {
            if (!preg_match(self::FILENAME_PATTERN, $name)) {
                continue;
            }
            $path = $dir . '/' . $name;
            $files[] = ['filename' => $name, 'size' => filesize($path), 'created_at' => filemtime($path)];
        }

        usort($files, static fn (array $a, array $b) => $b['created_at'] <=> $a['created_at']);

        return $files;
    }

    public static function path(string $filename): ?string
    {
        if (!preg_match(self::FILENAME_PATTERN, $filename)) {
            return null;
        }
        $path = self::dir() . '/' . $filename;
        return is_file($path) ? $path : null;
    }

    public static function delete(string $filename): void
    {
        $path = self::path($filename);
        if ($path) {
            unlink($path);
        }
    }

    /**
     * Decompresses the whole file to confirm it isn't corrupted and sanity-checks that it
     * actually looks like a database dump — without touching the database at all, so it's
     * safe to run any time. A backup nobody has ever opened is just a hope, not a plan.
     * @return array{0: bool, 1: string} [valid, message]
     */
    public static function verify(string $filename): array
    {
        $path = self::path($filename);
        if (!$path) {
            return [false, 'Backup file not found.'];
        }

        $handle = @gzopen($path, 'rb');
        if (!$handle) {
            return [false, 'Could not open the file as gzip — it may be corrupted.'];
        }

        $totalBytes = 0;
        $hasCreateTable = false;
        $tail = '';
        while (!gzeof($handle)) {
            $chunk = @gzread($handle, 262144);
            if ($chunk === false) {
                gzclose($handle);
                return [false, 'The file is corrupted (gzip read error partway through).'];
            }
            $totalBytes += strlen($chunk);
            if (!$hasCreateTable && str_contains($tail . $chunk, 'CREATE TABLE')) {
                $hasCreateTable = true;
            }
            $tail = substr($chunk, -20);
        }
        gzclose($handle);

        if ($totalBytes === 0) {
            return [false, 'The file decompresses to 0 bytes — empty backup.'];
        }

        // gzread()/gzeof() don't reliably detect a truncated stream (a transfer cut off
        // partway, or the disk filling up mid-write) — they'll happily report "done" on
        // whatever partial data was actually readable. The gzip format's own trailer (last
        // 4 bytes = original size mod 2^32) catches that: if it doesn't match what was
        // actually decompressed, the file is incomplete even though gzread() never errored.
        $raw = fopen($path, 'rb');
        fseek($raw, -4, SEEK_END);
        $trailer = fread($raw, 4);
        fclose($raw);
        $expectedSize = unpack('V', $trailer)[1] ?? null;
        if ($expectedSize === null || $expectedSize !== ($totalBytes % 4294967296)) {
            return [false, 'The file appears to be truncated (incomplete) — its size doesn\'t match the gzip trailer.'];
        }

        if (!$hasCreateTable) {
            return [false, "The file doesn't look like a real SQL dump (no CREATE TABLE found)."];
        }

        return [true, 'OK — decompressed cleanly (' . number_format($totalBytes) . ' bytes) and contains table definitions.'];
    }

    /**
     * Overwrites the live database with this backup — genuinely destructive, callers must
     * gate this behind a strong confirmation. Decompresses with PHP's own zlib (the same way
     * create() compresses — no external gunzip binary needed, just mysql itself) to a temp
     * .sql file, then feeds that straight into the mysql client via shell input redirection.
     * @return array{0: bool, 1: string} [success, message]
     */
    public static function restore(string $filename): array
    {
        $path = self::path($filename);
        if (!$path) {
            return [false, 'Backup file not found.'];
        }

        if (!self::execAvailable()) {
            return [false, 'PHP exec() is disabled on this server — restore is unavailable here.'];
        }

        $mysql = self::resolveBinary('mysql', App::config('backup.mysql_path'));
        if (!$mysql) {
            return [false, 'mysql client binary not found. Set MYSQL_PATH in .env to its absolute path.'];
        }

        $sqlPath = self::dir() . '/' . uniqid('restore_', true) . '.sql';
        if (!self::gunzipFile($path, $sqlPath)) {
            if (is_file($sqlPath)) {
                unlink($sqlPath);
            }
            return [false, 'Could not decompress the backup file — it may be corrupted. Try "Verify" first.'];
        }

        $config = App::config('db');
        $tmpConfig = tempnam(sys_get_temp_dir(), 'tmdbcnf');
        $lines = ['[client]', 'user="' . self::escapeIni($config['username']) . '"'];
        if ($config['password'] !== '') {
            $lines[] = 'password="' . self::escapeIni($config['password']) . '"';
        }
        if (!empty($config['socket'])) {
            $lines[] = 'socket="' . self::escapeIni($config['socket']) . '"';
        } else {
            $lines[] = 'host="' . self::escapeIni($config['host']) . '"';
            $lines[] = 'port="' . self::escapeIni((string) $config['port']) . '"';
        }
        file_put_contents($tmpConfig, implode("\n", $lines) . "\n");
        chmod($tmpConfig, 0600);

        $cmd = sprintf(
            '%s --defaults-extra-file=%s %s < %s 2>&1',
            escapeshellcmd($mysql),
            escapeshellarg($tmpConfig),
            escapeshellarg($config['database']),
            escapeshellarg($sqlPath)
        );

        exec($cmd, $outputLines, $exitCode);
        unlink($tmpConfig);
        unlink($sqlPath);

        if ($exitCode !== 0) {
            return [false, 'Restore failed: ' . implode(' ', $outputLines)];
        }

        return [true, 'Database restored from ' . $filename . '.'];
    }

    private static function prune(): void
    {
        $retention = max(1, (int) App::config('backup.retention'));
        $files = self::list();
        foreach (array_slice($files, $retention) as $old) {
            self::delete($old['filename']);
        }
    }

    private static function gzipFile(string $source, string $destination): bool
    {
        $in = fopen($source, 'rb');
        $out = gzopen($destination, 'wb9');
        if (!$in || !$out) {
            return false;
        }
        while (!feof($in)) {
            gzwrite($out, fread($in, 1024 * 512));
        }
        fclose($in);
        gzclose($out);
        return is_file($destination) && filesize($destination) > 0;
    }

    private static function gunzipFile(string $source, string $destination): bool
    {
        $in = @gzopen($source, 'rb');
        $out = @fopen($destination, 'wb');
        if (!$in || !$out) {
            return false;
        }
        while (!gzeof($in)) {
            $chunk = @gzread($in, 1024 * 512);
            if ($chunk === false) {
                gzclose($in);
                fclose($out);
                return false;
            }
            fwrite($out, $chunk);
        }
        gzclose($in);
        fclose($out);
        return is_file($destination) && filesize($destination) > 0;
    }

    /** @param string $name e.g. "mysqldump" or "mysql" */
    private static function resolveBinary(string $name, ?string $configured): ?string
    {
        // is_executable() is silenced here: on hosts with open_basedir restricted to the
        // vhost + /tmp (common on shared Plesk hosting), checking a system path like
        // /usr/bin/mysqldump emits a PHP warning even though the check itself still
        // correctly returns false — the real resolution then happens via the `command -v`
        // shell lookup below, which isn't subject to open_basedir.
        if ($configured && @is_executable($configured)) {
            return $configured;
        }

        foreach (["/usr/bin/{$name}", "/usr/local/bin/{$name}", "/usr/local/mysql/bin/{$name}"] as $candidate) {
            if (@is_executable($candidate)) {
                return $candidate;
            }
        }

        // Fall back to whatever it resolves to on PATH, if anything.
        exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null', $out, $code);
        return ($code === 0 && !empty($out[0])) ? trim($out[0]) : null;
    }

    private static function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return !in_array('exec', $disabled, true);
    }

    private static function escapeIni(string $value): string
    {
        return str_replace('"', '\\"', $value);
    }
}
