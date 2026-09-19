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

        $mysqldump = self::resolveBinary();
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

    private static function resolveBinary(): ?string
    {
        $configured = App::config('backup.mysqldump_path');
        if ($configured && is_executable($configured)) {
            return $configured;
        }

        foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/usr/local/mysql/bin/mysqldump'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        // Fall back to whatever "mysqldump" resolves to on PATH, if anything.
        exec('command -v mysqldump 2>/dev/null', $out, $code);
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
