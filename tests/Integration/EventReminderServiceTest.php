<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Lot;
use App\Services\EventReminderService;
use Tests\TestCase;

/**
 * Covers the due-event selection and idempotency of EventReminderService —
 * the mail/LINE send calls themselves are exercised indirectly (Mailer falls
 * back to PHP's mail() with no SMTP configured locally, which fails fast and
 * harmlessly; LineService::push() no-ops without a configured channel token),
 * so this focuses on what's actually our logic: which events are due, and that
 * a second run never reminds the same event twice.
 *
 * sendDueReminders() has no way to scope itself to specific event ids — it's
 * a real "process everything due" call — so this saves/restores the global
 * vendor_reminder_days_before setting and checks no other real event in the
 * dev DB was sitting in the reminder window before actually invoking it.
 */
class EventReminderServiceTest extends TestCase
{
    private int $dueEventId;
    private int $farEventId;
    private int $dueLotId;
    private ?string $originalDaysBefore = null;

    public function setUp(): void
    {
        $pdo = Database::connection();
        $this->originalDaysBefore = $pdo->query('SELECT vendor_reminder_days_before FROM settings WHERE id = 1')->fetchColumn();
        $pdo->prepare('UPDATE settings SET vendor_reminder_days_before = 3 WHERE id = 1')->execute();

        $this->dueEventId = $this->createEvent('[TEST] งานใกล้ถึง', '+2 days');
        $this->farEventId = $this->createEvent('[TEST] งานยังอีกนาน', '+30 days');
        $this->dueLotId = Lot::create($this->dueEventId, null, 'TEST-REM1', 100.00);

        Booking::create([
            'booking_code' => 'TMREM' . strtoupper(bin2hex(random_bytes(3))),
            'event_id' => $this->dueEventId,
            'lot_id' => $this->dueLotId,
            'booker_name' => 'ผู้ทดสอบ',
            'booker_phone' => '0899990088',
            'booker_email' => null,
            'payment_method' => 'onsite_cash',
            'status' => 'booked',
            'price_at_booking' => 100.00,
            'currency_code' => 'THB',
        ]);
    }

    public function tearDown(): void
    {
        $pdo = Database::connection();
        foreach ([$this->dueEventId, $this->farEventId] as $eventId) {
            $pdo->prepare('DELETE FROM booking_status_logs WHERE booking_id IN (SELECT id FROM bookings WHERE event_id = ?)')
                ->execute([$eventId]);
            $pdo->prepare('DELETE FROM bookings WHERE event_id = ?')->execute([$eventId]);
            $pdo->prepare('DELETE FROM lots WHERE event_id = ?')->execute([$eventId]);
            $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$eventId]);
        }
        if ($this->originalDaysBefore !== null) {
            $pdo->prepare('UPDATE settings SET vendor_reminder_days_before = ? WHERE id = 1')
                ->execute([$this->originalDaysBefore]);
        }
    }

    public function testOnlyTheEventWithinTheWindowIsDue(): void
    {
        $due = Event::dueForVendorReminder(3);
        $dueIds = array_map(fn ($e) => (int) $e['id'], $due);

        $this->assertTrue(in_array($this->dueEventId, $dueIds, true), 'an event 2 days out should be due at a 3-day reminder window');
        $this->assertFalse(in_array($this->farEventId, $dueIds, true), 'an event 30 days out should not be due yet');
    }

    public function testSendingRemindersMarksTheEventSoASecondRunSkipsIt(): void
    {
        $before = Event::find($this->dueEventId);
        $this->assertNull($before['vendor_reminder_sent_at']);

        // sendDueReminders() processes every due event globally, not just this
        // test's — refuse to run it for real if some other real event in the dev
        // DB is unexpectedly sitting in the reminder window right now.
        $dueNow = Event::dueForVendorReminder(3);
        $unexpectedlyDue = array_filter($dueNow, fn ($e) => (int) $e['id'] !== $this->dueEventId);
        $this->assertSame([], array_values($unexpectedlyDue), 'expected only the test event to be due — aborting to avoid touching real events');

        $count1 = EventReminderService::sendDueReminders();
        $this->assertSame(1, $count1, 'expected exactly the one due test event to be processed');

        $after = Event::find($this->dueEventId);
        $this->assertNotNull($after['vendor_reminder_sent_at']);

        $stillDue = Event::dueForVendorReminder(3);
        $stillDueIds = array_map(fn ($e) => (int) $e['id'], $stillDue);
        $this->assertFalse(in_array($this->dueEventId, $stillDueIds, true), 'an already-reminded event must not be due again');
    }

    private function createEvent(string $name, string $startOffset): int
    {
        $start = date('Y-m-d', strtotime($startOffset));
        return Event::create([
            'slug' => 'test-reminder-' . bin2hex(random_bytes(4)),
            'name_th' => $name,
            'name_en' => null,
            'description_th' => null,
            'description_en' => null,
            'venue_name' => null,
            'start_date' => $start,
            'end_date' => $start,
            'booking_open_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'booking_close_at' => date('Y-m-d H:i:s', strtotime($startOffset . ' -1 day')),
            'is_published' => 1,
        ]);
    }
}
