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
    private string $path;

    public function setUp(): void
    {
        $this->path = BASE_PATH . '/database/upgrades/' . self::TEST_FILENAME;
    }

    public function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
        $pdo = Database::connection();
        $pdo->exec('DROP TABLE IF EXISTS _test_migration_marker');
        $pdo->prepare('DELETE FROM schema_migrations WHERE filename = ?')->execute([self::TEST_FILENAME]);
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
}
