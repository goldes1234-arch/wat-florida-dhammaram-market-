<?php

/**
 * Mock data seeder — safe to re-run (truncates first).
 * Usage: /Applications/XAMPP/xamppfiles/bin/php database/seed.php
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Core/autoload.php';

use App\Core\App;
use App\Core\Database;
use App\Core\Env;

Env::load(BASE_PATH . '/.env');
App::boot(require BASE_PATH . '/config/config.php');

$pdo = Database::connection();

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['booking_rate_limits', 'interest_subscribers', 'booking_status_logs', 'bookings', 'lots', 'zones', 'events', 'admin_users', 'settings'] as $table) {
    $pdo->exec("TRUNCATE TABLE `{$table}`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

// ---------------------------------------------------------------- settings
$pdo->prepare(
    'INSERT INTO settings
        (id, org_name, org_address, org_phone, org_email, facebook_url, line_oa_id, google_maps_url,
         bank_name, bank_account_name, bank_account_number, promptpay_id,
         currency_code, default_locale, cancellation_cutoff_days, booking_rate_limit_per_hour)
     VALUES
        (1, :org_name, :org_address, :org_phone, :org_email, :facebook_url, :line_oa_id, :google_maps_url,
         :bank_name, :bank_account_name, :bank_account_number, :promptpay_id,
         "THB", "th", 3, 5)'
)->execute([
    'org_name' => 'วัดโพธิ์เย็น',
    'org_address' => '99 หมู่ 4 ตำบลบางพลี อำเภอบางพลี จังหวัดสมุทรปราการ 10540',
    'org_phone' => '02-123-4567',
    'org_email' => 'contact@watphoyen-market.local',
    'facebook_url' => 'https://facebook.com/watphoyen',
    'line_oa_id' => '@watphoyen',
    'google_maps_url' => 'https://maps.google.com/?q=วัดโพธิ์เย็น',
    'bank_name' => 'ธนาคารกรุงไทย',
    'bank_account_name' => 'วัดโพธิ์เย็น (เพื่อการจัดงาน)',
    'bank_account_number' => '123-4-56789-0',
    'promptpay_id' => '0812345678',
]);
echo "settings seeded\n";

// ---------------------------------------------------------------- admin_users
$pdo->prepare(
    'INSERT INTO admin_users (name, email, password_hash, role, is_active) VALUES (:name, :email, :hash, "super_admin", 1)'
)->execute([
    'name' => 'ผู้ดูแลระบบ',
    'email' => 'admin@templemarket.local',
    'hash' => password_hash('Passw0rd!2026', PASSWORD_DEFAULT),
]);
echo "admin_users seeded (login: admin@templemarket.local / Passw0rd!2026)\n";

// ---------------------------------------------------------------- events
function insertEvent(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO events
            (slug, name_th, description_th, venue_name, start_date, end_date,
             booking_open_at, booking_close_at, banner_image, floorplan_image, is_published)
         VALUES
            (:slug, :name_th, :description_th, :venue_name, :start_date, :end_date,
             :booking_open_at, :booking_close_at, NULL, NULL, :is_published)'
    );
    $stmt->execute($data);
    return (int) $pdo->lastInsertId();
}

$eventA = insertEvent($pdo, [
    'slug' => 'loy-krathong-2569',
    'name_th' => 'งานวัดลอยกระทง ประจำปี 2569',
    'description_th' => 'งานประจำปีวัดโพธิ์เย็น พบกับตลาดนัดกลางคืน การแสดงศิลปวัฒนธรรม และการประกวดกระทงฝีมือดี เปิดให้ผู้สนใจจองแผงขายของล่วงหน้า',
    'venue_name' => 'ลานวัดโพธิ์เย็น',
    'start_date' => '2026-11-24',
    'end_date' => '2026-11-26',
    'booking_open_at' => '2026-10-15 00:00:00',
    'booking_close_at' => '2026-11-20 23:59:59',
    'is_published' => 1,
]);

$eventB = insertEvent($pdo, [
    'slug' => 'talat-nat-chumchon-12',
    'name_th' => 'ตลาดนัดชุมชนวัดโพธิ์เย็น ครั้งที่ 12',
    'description_th' => 'ตลาดนัดชุมชนประจำเดือน จำหน่ายอาหาร สินค้าทำมือ และของใช้ทั่วไป เปิดให้จองแผงล่วงหน้าผ่านระบบออนไลน์',
    'venue_name' => 'ลานจอดรถวัดโพธิ์เย็น',
    'start_date' => '2026-09-26',
    'end_date' => '2026-09-27',
    'booking_open_at' => '2026-09-10 00:00:00',
    'booking_close_at' => '2026-09-24 23:59:59',
    'is_published' => 1,
]);

$eventC = insertEvent($pdo, [
    'slug' => 'boon-kathin-samakkhi-2569',
    'name_th' => 'งานบุญกฐินสามัคคี วัดสามพราน 2569',
    'description_th' => 'งานทอดกฐินสามัคคีประจำปี พร้อมตลาดนัดการกุศลภายในงาน',
    'venue_name' => 'ลานวัดสามพราน',
    'start_date' => '2026-08-01',
    'end_date' => '2026-08-02',
    'booking_open_at' => '2026-07-01 00:00:00',
    'booking_close_at' => '2026-07-28 23:59:59',
    'is_published' => 1,
]);

$eventD = insertEvent($pdo, [
    'slug' => 'kachad-annual-mai',
    'name_th' => 'งานกาชาดประจำปี วัดใหม่ (ฉบับร่าง)',
    'description_th' => 'ฉบับร่าง อยู่ระหว่างเตรียมข้อมูลก่อนเผยแพร่',
    'venue_name' => 'ลานวัดใหม่',
    'start_date' => '2026-12-05',
    'end_date' => '2026-12-06',
    'booking_open_at' => '2026-11-01 00:00:00',
    'booking_close_at' => '2026-12-01 23:59:59',
    'is_published' => 0,
]);
echo "events seeded (A=$eventA coming_soon, B=$eventB open, C=$eventC closed, D=$eventD draft)\n";

// ---------------------------------------------------------------- zones
function insertZone(PDO $pdo, int $eventId, string $name, float $price, int $sort): int
{
    $stmt = $pdo->prepare('INSERT INTO zones (event_id, name, default_price, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$eventId, $name, $price, $sort]);
    return (int) $pdo->lastInsertId();
}

$zoneA1 = insertZone($pdo, $eventA, 'โซน A (ริมน้ำ)', 800, 1);
$zoneA2 = insertZone($pdo, $eventA, 'โซน B (ทั่วไป)', 500, 2);
$zoneB1 = insertZone($pdo, $eventB, 'โซนอาหาร', 300, 1);
$zoneB2 = insertZone($pdo, $eventB, 'โซนสินค้าทั่วไป', 200, 2);
echo "zones seeded\n";

// ---------------------------------------------------------------- lots
function insertLot(PDO $pdo, int $eventId, ?int $zoneId, string $code, float $price, string $status): int
{
    $stmt = $pdo->prepare('INSERT INTO lots (event_id, zone_id, code, price, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$eventId, $zoneId, $code, $price, $status]);
    return (int) $pdo->lastInsertId();
}

$lots = [];

for ($n = 1; $n <= 5; $n++) {
    $lots['A-' . sprintf('%02d', $n)] = insertLot($pdo, $eventA, $zoneA1, 'A-' . sprintf('%02d', $n), 800, 'available');
}
for ($n = 1; $n <= 5; $n++) {
    $lots['B-' . sprintf('%02d', $n)] = insertLot($pdo, $eventA, $zoneA2, 'B-' . sprintf('%02d', $n), 500, 'available');
}

$fStatuses = ['F-01' => 'booked', 'F-02' => 'pending_payment', 'F-03' => 'available', 'F-04' => 'available', 'F-05' => 'available', 'F-06' => 'available'];
foreach ($fStatuses as $code => $status) {
    $lots[$code] = insertLot($pdo, $eventB, $zoneB1, $code, 300, $status);
}
$gStatuses = ['G-01' => 'booked', 'G-02' => 'booked', 'G-03' => 'pending_payment', 'G-04' => 'available', 'G-05' => 'available', 'G-06' => 'available'];
foreach ($gStatuses as $code => $status) {
    $lots[$code] = insertLot($pdo, $eventB, $zoneB2, $code, 200, $status);
}

$cStatuses = ['01' => 'booked', '02' => 'booked', '03' => 'booked', '04' => 'booked', '05' => 'booked', '06' => 'booked', '07' => 'available', '08' => 'available'];
foreach ($cStatuses as $code => $status) {
    $lots['C-' . $code] = insertLot($pdo, $eventC, null, $code, 350, $status);
}
echo 'lots seeded (' . count($lots) . " total)\n";

// ---------------------------------------------------------------- bookings + status logs
function insertBooking(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO bookings
            (booking_code, event_id, lot_id, booker_name, booker_phone, booker_email,
             payment_method, status, price_at_booking, currency_code, confirmed_at, cancelled_at, cancelled_by, admin_note, created_at)
         VALUES
            (:booking_code, :event_id, :lot_id, :booker_name, :booker_phone, :booker_email,
             :payment_method, :status, :price_at_booking, "THB", :confirmed_at, :cancelled_at, :cancelled_by, :admin_note, :created_at)'
    );
    $stmt->execute($data);
    return (int) $pdo->lastInsertId();
}

function insertLog(PDO $pdo, int $bookingId, ?string $from, string $to, string $byType, ?string $note, string $createdAt): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO booking_status_logs (booking_id, from_status, to_status, changed_by_type, note, created_at)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$bookingId, $from, $to, $byType, $note, $createdAt]);
}

$bookingsSeed = [
    // Event B
    ['code' => 'TM-7F3KQ2', 'lot' => 'F-01', 'event' => $eventB, 'name' => 'สมชาย ใจดี', 'phone' => '081-234-5678', 'email' => 'somchai.j@example.com', 'method' => 'onsite_cash', 'status' => 'booked', 'price' => 300, 'created' => '2026-09-14 09:15:00', 'confirmed' => '2026-09-15 10:00:00'],
    ['code' => 'TM-9K2M4P', 'lot' => 'F-02', 'event' => $eventB, 'name' => 'มาลี ขายดี', 'phone' => '086-111-2233', 'email' => null, 'method' => 'onsite_cash', 'status' => 'pending_payment', 'price' => 300, 'created' => '2026-09-16 14:20:00'],
    ['code' => 'TM-4Q7R9T', 'lot' => 'G-01', 'event' => $eventB, 'name' => 'สุพรรณี รุ่งเรือง', 'phone' => '089-876-5432', 'email' => 'supannee.r@example.com', 'method' => 'bank_transfer', 'status' => 'booked', 'price' => 200, 'created' => '2026-09-13 11:00:00', 'confirmed' => '2026-09-14 09:00:00'],
    ['code' => 'TM-2Z8X5N', 'lot' => 'G-02', 'event' => $eventB, 'name' => 'ประยุทธ์ ค้าขาย', 'phone' => '092-345-6781', 'email' => 'prayut.trade@example.com', 'method' => 'stripe', 'status' => 'booked', 'price' => 200, 'created' => '2026-09-12 08:30:00', 'confirmed' => '2026-09-12 08:35:00'],
    ['code' => 'TM-6W3H8J', 'lot' => 'G-03', 'event' => $eventB, 'name' => 'วิชัย ทองดี', 'phone' => '084-222-3344', 'email' => 'wichai.t@example.com', 'method' => 'stripe', 'status' => 'pending_payment', 'price' => 200, 'created' => '2026-09-17 19:45:00'],
    ['code' => 'TM-5T9Y2C', 'lot' => 'G-05', 'event' => $eventB, 'name' => 'อรทัย ทำมาหากิน', 'phone' => '080-555-6677', 'email' => 'orathai.w@example.com', 'method' => 'bank_transfer', 'status' => 'rejected', 'price' => 200, 'created' => '2026-09-14 08:00:00', 'reject_note' => 'ไม่พบยอดโอนเงินตามที่แจ้ง ติดต่อไม่ได้', 'rejected_at' => '2026-09-14 20:00:00'],
    ['code' => 'TM-3B6D1L', 'lot' => 'G-06', 'event' => $eventB, 'name' => 'กมล ค้าคล่อง', 'phone' => '087-333-4455', 'email' => 'kamon.k@example.com', 'method' => 'onsite_cash', 'status' => 'cancelled', 'price' => 200, 'created' => '2026-09-11 16:00:00', 'cancelled_at' => '2026-09-12 09:00:00', 'cancelled_by' => 'guest'],

    // Event C (historical, all confirmed booked)
    ['code' => 'TM-C1A2B3', 'lot' => 'C-01', 'event' => $eventC, 'name' => 'บุญมี ศรัทธาดี', 'phone' => '081-111-0001', 'email' => 'boonmee.s@example.com', 'method' => 'onsite_cash', 'status' => 'booked', 'price' => 350, 'created' => '2026-07-05 10:00:00', 'confirmed' => '2026-07-05 10:05:00'],
    ['code' => 'TM-C2D4E5', 'lot' => 'C-02', 'event' => $eventC, 'name' => 'ทองใบ มั่งมี', 'phone' => '081-111-0002', 'email' => 'thongbai.m@example.com', 'method' => 'bank_transfer', 'status' => 'booked', 'price' => 350, 'created' => '2026-07-08 13:20:00', 'confirmed' => '2026-07-09 09:00:00'],
    ['code' => 'TM-C3F6G7', 'lot' => 'C-03', 'event' => $eventC, 'name' => 'ทองสุข การค้า', 'phone' => '081-111-0003', 'email' => 'thongsuk.k@example.com', 'method' => 'stripe', 'status' => 'booked', 'price' => 350, 'created' => '2026-07-10 15:40:00', 'confirmed' => '2026-07-10 15:42:00'],
    ['code' => 'TM-C4H8J9', 'lot' => 'C-04', 'event' => $eventC, 'name' => 'รัตนา พูนสุข', 'phone' => '081-111-0004', 'email' => 'rattana.p@example.com', 'method' => 'onsite_cash', 'status' => 'booked', 'price' => 350, 'created' => '2026-07-15 11:10:00', 'confirmed' => '2026-07-15 11:15:00'],
    ['code' => 'TM-C5K1L2', 'lot' => 'C-05', 'event' => $eventC, 'name' => 'สมหญิง ขายเก่ง', 'phone' => '081-111-0005', 'email' => 'somying.k@example.com', 'method' => 'bank_transfer', 'status' => 'booked', 'price' => 350, 'created' => '2026-07-20 16:00:00', 'confirmed' => '2026-07-21 08:30:00'],
    ['code' => 'TM-C6M3N4', 'lot' => 'C-06', 'event' => $eventC, 'name' => 'อนันต์ รวยทรัพย์', 'phone' => '081-111-0006', 'email' => 'anan.r@example.com', 'method' => 'stripe', 'status' => 'booked', 'price' => 350, 'created' => '2026-07-25 09:00:00', 'confirmed' => '2026-07-25 09:03:00'],
];

$logCount = 0;
foreach ($bookingsSeed as $b) {
    $bookingId = insertBooking($pdo, [
        'booking_code' => $b['code'],
        'event_id' => $b['event'],
        'lot_id' => $lots[$b['lot']],
        'booker_name' => $b['name'],
        'booker_phone' => $b['phone'],
        'booker_email' => $b['email'],
        'payment_method' => $b['method'],
        'status' => $b['status'],
        'price_at_booking' => $b['price'],
        'confirmed_at' => $b['confirmed'] ?? null,
        'cancelled_at' => $b['cancelled_at'] ?? $b['rejected_at'] ?? null,
        'cancelled_by' => $b['cancelled_by'] ?? null,
        'admin_note' => $b['reject_note'] ?? null,
        'created_at' => $b['created'],
    ]);

    insertLog($pdo, $bookingId, null, 'pending_payment', 'guest', 'สร้างรายการจอง', $b['created']);
    $logCount++;

    if ($b['status'] === 'booked') {
        insertLog($pdo, $bookingId, 'pending_payment', 'booked', $b['method'] === 'stripe' ? 'system' : 'admin', 'ยืนยันการรับชำระเงินแล้ว', $b['confirmed']);
        $logCount++;
    } elseif ($b['status'] === 'rejected') {
        insertLog($pdo, $bookingId, 'pending_payment', 'rejected', 'admin', $b['reject_note'], $b['rejected_at']);
        $logCount++;
    } elseif ($b['status'] === 'cancelled') {
        insertLog($pdo, $bookingId, 'pending_payment', 'cancelled', 'guest', 'ผู้จองยกเลิกด้วยตนเอง', $b['cancelled_at']);
        $logCount++;
    }
}
echo 'bookings seeded (' . count($bookingsSeed) . " total, $logCount status log rows)\n";

// ---------------------------------------------------------------- interest_subscribers
function insertSubscriber(PDO $pdo, int $eventId, ?string $email, ?string $phone, string $createdAt): void
{
    $stmt = $pdo->prepare('INSERT INTO interest_subscribers (event_id, email, phone, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$eventId, $email, $phone, $createdAt]);
}

insertSubscriber($pdo, $eventA, 'nid.vendor@example.com', null, '2026-09-16 10:00:00');
insertSubscriber($pdo, $eventA, null, '089-777-8899', '2026-09-16 15:30:00');
insertSubscriber($pdo, $eventA, 'waree.shop@example.com', '082-123-9876', '2026-09-17 08:45:00');
echo "interest_subscribers seeded (3 total)\n";

echo "\nDone. Admin login: admin@templemarket.local / Passw0rd!2026\n";
