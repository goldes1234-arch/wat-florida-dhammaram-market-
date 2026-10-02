<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Services\MigrationService;
use Tests\TestCase;

/**
 * Assumes schema_migrations already has at least one row (true for this project
 * from the moment MigrationService first runs) — on a genuinely empty tracker
 * table, runPending() takes its bootstrap path instead (marks everything as
 * already-applied without running it), which this test doesn't cover.
 */
class MigrationServiceTest extends TestCase
{
    private const TEST_FILENAME = 'zzz_test_migration_service.sql';
    private const TEST_FILENAME_NO_DOWN = 'zzz_test_migration_no_down.sql';
    private string $path;
    private string $downPath;
    private string $noDownPath;

    public function setUp(): void
    {
        $this->path = BASE_PATH . '/database/upgrades/' . self::TEST_FILENAME;
        $this->downPath = BASE_PATH . '/database/upgrades/zzz_test_migration_service.down.sql';
        $this->noDownPath = BASE_PATH . '/database/upgrades/' . self::TEST_FILENAME_NO_DOWN;
    }

    public function tearDown(): void
    {
        foreach ([$this->path, $this->downPath, $this->noDownPath] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $pdo = Database::connection();
        $pdo->exec('DROP TABLE IF EXISTS _test_migration_marker');
        $pdo->prepare('DELETE FROM schema_migrations WHERE filename IN (?, ?)')
            ->execute([self::TEST_FILENAME, self::TEST_FILENAME_NO_DOWN]);
    }

    public function testPendingFileIsAppliedAndTrackedThenSkippedOnRerun(): void
    {
        file_put_contents(
            $this->path,
            "CREATE TABLE _test_migration_marker (id INT UNSIGNED NOT NULL PRIMARY KEY);\n"
        );

        $ran = MigrationService::runPending();
        $this->assertTrue(in_array(self::TEST_FILENAME, $ran, true), 'expected the new file to be reported as run');

        $pdo = Database::connection();
        $exists = (bool) $pdo->query("SHOW TABLES LIKE '_test_migration_marker'")->fetch();
        $this->assertTrue($exists, 'expected the migration SQL to have actually executed, not just been recorded');

        $ranAgain = MigrationService::runPending();
        $this->assertFalse(in_array(self::TEST_FILENAME, $ranAgain, true), 'expected an already-applied file not to re-run');
    }

    public function testDownFileIsNeverTreatedAsItsOwnPendingMigration(): void
    {
        file_put_contents(
            $this->path,
            "CREATE TABLE _test_migration_marker (id INT UNSIGNED NOT NULL PRIMARY KEY);\n"
        );
        file_put_contents($this->downPath, "DROP TABLE _test_migration_marker;\n");

        $ran = MigrationService::runPending();

        $this->assertTrue(in_array(self::TEST_FILENAME, $ran, true), 'the real migration should still run');
        $this->assertFalse(
            in_array('zzz_test_migration_service.down.sql', $ran, true),
            'a .down.sql file must never be picked up as a pending migration of its own'
        );
    }

    public function testRollbackLastUndoesTheMigrationAndRemovesItsTrackingRow(): void
    {
        file_put_contents(
            $this->path,
            "CREATE TABLE _test_migration_marker (id INT UNSIGNED NOT NULL PRIMARY KEY);\n"
        );
        file_put_contents($this->downPath, "DROP TABLE _test_migration_marker;\n");
        MigrationService::runPending();

        [$ok, $result] = MigrationService::rollbackLast();

        $this->assertTrue($ok, 'expected the rollback to succeed since a .down.sql file exists');
        $this->assertSame(self::TEST_FILENAME, $result);

        $pdo = Database::connection();
        $tableExists = (bool) $pdo->query("SHOW TABLES LIKE '_test_migration_marker'")->fetch();
        $this->assertFalse($tableExists, 'expected the down.sql to have actually run and dropped the table');

        $stillTracked = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE filename = ?');
        $stillTracked->execute([self::TEST_FILENAME]);
        $this->assertSame(0, (int) $stillTracked->fetchColumn(), 'expected the tracking row to be removed on rollback');
    }

    public function testRollbackLastFailsCleanlyWhenNoDownFileExists(): void
    {
        // A SET statement rather than SELECT — PDO::exec() on a SELECT leaves an
        // unconsumed result set dangling on the shared connection, breaking every
        // query run afterward for the rest of the test suite.
        file_put_contents($this->noDownPath, "SET @zzz_test_no_down_marker = 1;\n");
        MigrationService::runPending();

        [$ok, $message] = MigrationService::rollbackLast();

        $this->assertFalse($ok, 'expected rollback to refuse without a .down.sql file');
        $this->assertTrue(
            str_contains($message, self::TEST_FILENAME_NO_DOWN),
            'expected the failure message to name the file that has no rollback available'
        );
    }
}
