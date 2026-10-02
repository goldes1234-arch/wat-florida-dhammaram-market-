<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Applies pending database/upgrades/*.sql files automatically and records which
 * ones have run, in a schema_migrations table it creates on first use — so a
 * deploy can never again go live with code that expects a column the database
 * doesn't have yet (see CronController::postDeploy()).
 *
 * The FIRST time this runs, every upgrade file already in the repo is marked as
 * applied without being executed: this whole project's history so far was
 * applied by hand file-by-file, so replaying all of it here would just error on
 * columns that already exist. Only files added after that bootstrap moment are
 * actually run.
 *
 * Rollback: a migration can only be undone if its author wrote a companion
 * `<name>.down.sql` file alongside `<name>.sql` — that's optional (many schema
 * changes, like backfills or anything that already dropped a column, can't be
 * safely reversed by SQL alone), so most of this project's existing migration
 * history has no down file and simply isn't rollback-able; restoring the most
 * recent database backup is the fallback for those. rollbackLast() only ever
 * touches the single most-recently-applied migration, never a whole chain.
 */
class MigrationService
{
    private const TABLE = 'schema_migrations';

    public static function runPending(): array
    {
        $pdo = Database::connection();
        self::ensureTable($pdo);

        $files = self::upgradeFiles();

        $alreadyTracked = (int) $pdo->query('SELECT COUNT(*) FROM ' . self::TABLE)->fetchColumn();

        if ($alreadyTracked === 0) {
            $insert = $pdo->prepare('INSERT INTO ' . self::TABLE . ' (filename) VALUES (:filename)');
            foreach ($files as $file) {
                $insert->execute(['filename' => basename($file)]);
            }
            return [];
        }

        $applied = $pdo->query('SELECT filename FROM ' . self::TABLE)->fetchAll(PDO::FETCH_COLUMN);
        $appliedSet = array_flip($applied);

        $ran = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (isset($appliedSet[$name])) {
                continue;
            }

            $sql = (string) file_get_contents($file);
            $pdo->exec($sql);

            $pdo->prepare('INSERT INTO ' . self::TABLE . ' (filename) VALUES (:filename)')
                ->execute(['filename' => $name]);
            $ran[] = $name;
        }

        return $ran;
    }

    /** @return array<int, array{filename: string, applied_at: string, rollback_available: bool}> newest first */
    public static function appliedHistory(): array
    {
        $pdo = Database::connection();
        self::ensureTable($pdo);

        $rows = $pdo->query('SELECT filename, applied_at FROM ' . self::TABLE . ' ORDER BY applied_at DESC, filename DESC')
            ->fetchAll();

        foreach ($rows as &$row) {
            $row['rollback_available'] = is_file(self::downFileFor($row['filename']));
        }

        return $rows;
    }

    /**
     * Undoes only the single most-recently-applied migration, and only if it has a
     * companion `<name>.down.sql` file. @return array{0: bool, 1: string} [success, message]
     */
    public static function rollbackLast(): array
    {
        $pdo = Database::connection();
        self::ensureTable($pdo);

        $stmt = $pdo->query('SELECT filename FROM ' . self::TABLE . ' ORDER BY applied_at DESC, filename DESC LIMIT 1');
        $last = $stmt->fetchColumn();
        $stmt->closeCursor();

        if (!$last) {
            return [false, 'No applied migration to roll back.'];
        }

        $downFile = self::downFileFor($last);
        if (!is_file($downFile)) {
            return [false, "No {$last} rollback file found (" . basename($downFile) . ') — restore from a backup instead.'];
        }

        $pdo->exec((string) file_get_contents($downFile));
        $pdo->prepare('DELETE FROM ' . self::TABLE . ' WHERE filename = :filename')->execute(['filename' => $last]);

        return [true, $last];
    }

    private static function ensureTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
                filename VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return string[] sorted *.sql upgrade files, excluding *.down.sql rollback companions */
    private static function upgradeFiles(): array
    {
        $files = glob(self::upgradesDir() . '/*.sql') ?: [];
        $files = array_filter($files, static fn (string $f) => !str_ends_with($f, '.down.sql'));
        sort($files);
        return array_values($files);
    }

    private static function downFileFor(string $filename): string
    {
        return self::upgradesDir() . '/' . substr($filename, 0, -4) . '.down.sql';
    }

    private static function upgradesDir(): string
    {
        return BASE_PATH . '/database/upgrades';
    }
}
