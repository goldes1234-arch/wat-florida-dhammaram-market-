<?php

namespace Tests\Integration;

use App\Core\Auth;
use App\Core\Database;
use App\Core\EventAccess;
use App\Core\Session;
use App\Models\AdminUser;
use App\Models\Event;
use App\Models\StaffEventAccess;
use Tests\TestCase;

/**
 * Exercises the "opt-in restriction" behavior EventAccess/StaffEventAccess give
 * a 'staff' admin: zero assignments means unrestricted (unchanged from before
 * this feature existed), and assigning at least one event switches that
 * account to an allow-list of only those events.
 */
class EventAccessTest extends TestCase
{
    private int $staffId;
    private int $eventA;
    private int $eventB;

    public function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->staffId = AdminUser::create([
            'name' => '[TEST] staff',
            'email' => 'test-eventaccess-' . bin2hex(random_bytes(4)) . '@example.invalid',
            'password_hash' => password_hash('irrelevant', PASSWORD_DEFAULT),
            'role' => 'staff',
        ]);
        $this->eventA = $this->createTestEvent('A');
        $this->eventB = $this->createTestEvent('B');

        Session::put('admin_id', $this->staffId);
        $this->resetAuthCache();
    }

    public function tearDown(): void
    {
        Session::forget('admin_id');
        $this->resetAuthCache();

        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM staff_event_access WHERE admin_id = ?')->execute([$this->staffId]);
        $pdo->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$this->staffId]);
        $pdo->prepare('DELETE FROM events WHERE id IN (?, ?)')->execute([$this->eventA, $this->eventB]);
    }

    public function testStaffWithNoAssignmentsIsUnrestricted(): void
    {
        $this->assertNull(EventAccess::assignedEventIds());
        $this->assertTrue(EventAccess::allowed($this->eventA));
        $this->assertTrue(EventAccess::allowed($this->eventB));
        $this->assertFalse(EventAccess::isRestricted());
    }

    public function testAssigningOneEventRestrictsToOnlyThat(): void
    {
        StaffEventAccess::setFor($this->staffId, [$this->eventA]);

        $this->assertTrue(EventAccess::isRestricted());
        $this->assertTrue(EventAccess::allowed($this->eventA));
        $this->assertFalse(EventAccess::allowed($this->eventB));
    }

    public function testFilterEventsKeepsOnlyAssignedRows(): void
    {
        StaffEventAccess::setFor($this->staffId, [$this->eventA]);

        $rows = [['id' => $this->eventA, 'name' => 'A'], ['id' => $this->eventB, 'name' => 'B']];
        $filtered = EventAccess::filterEvents($rows);

        $this->assertSame(1, count($filtered));
        $this->assertSame($this->eventA, $filtered[0]['id']);
    }

    public function testClearingAssignmentsReturnsToUnrestricted(): void
    {
        StaffEventAccess::setFor($this->staffId, [$this->eventA]);
        $this->assertTrue(EventAccess::isRestricted());

        StaffEventAccess::setFor($this->staffId, []);
        $this->assertFalse(EventAccess::isRestricted());
        $this->assertTrue(EventAccess::allowed($this->eventB));
    }

    private function createTestEvent(string $label): int
    {
        return Event::create([
            'slug' => 'test-eventaccess-' . strtolower($label) . '-' . bin2hex(random_bytes(4)),
            'name_th' => "[TEST] งาน $label",
            'name_en' => "[TEST] Event $label",
            'description_th' => null,
            'description_en' => null,
            'venue_name' => null,
            'start_date' => date('Y-m-d', strtotime('+60 days')),
            'end_date' => date('Y-m-d', strtotime('+60 days')),
            'booking_open_at' => date('Y-m-d H:i:s'),
            'booking_close_at' => date('Y-m-d H:i:s', strtotime('+59 days')),
            'is_published' => 0,
        ]);
    }

    /** Auth caches the resolved user in private static properties for the life of the process — tests need a clean slate each time. */
    private function resetAuthCache(): void
    {
        $ref = new \ReflectionClass(Auth::class);
        $cache = $ref->getProperty('userCache');
        $cache->setAccessible(true);
        $cache->setValue(null, null);
        $resolved = $ref->getProperty('resolved');
        $resolved->setAccessible(true);
        $resolved->setValue(null, false);
    }
}
