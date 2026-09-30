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
 */
class MigrationService
{
    private const TABLE = 'schema_migrations';

    public static function runPending(): array
    {
        $pdo = Database::connection();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
                filename VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $files = glob(self::upgradesDir() . '/*.sql') ?: [];
        sort($files);

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

    private static function upgradesDir(): string
    {
        return BASE_PATH . '/database/upgrades';
    }
}
