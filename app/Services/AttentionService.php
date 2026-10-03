<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Setting;

/**
 * "What needs doing right now" for the admin dashboard: each item is a count plus where to go to deal with it.
 * Finance accounts only see money-related items.
 */
class AttentionService
{
    /** A daily backup is overdue after this many hours (cron runs once a day, plus slack). */
    public const BACKUP_STALE_HOURS = 30;

    /** @return list<array{key:string, level:string, count:int, label:string, url:string}> highest-priority first */
    public static function items(): array
    {
        $pdo = Database::connection();
        $items = [];

        $pending = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending_payment'")->fetchColumn();
        if ($pending > 0) {
            $items[] = self::item('pending_payments', 'warn', $pending, __('attention.pending_payments', ['count' => (string) $pending]), 'admin/bookings?status=pending_payment');
        }

        if (Auth::isFinance()) {
            return $items;
        }

        $linkRequests = (int) $pdo->query('SELECT COUNT(*) FROM vendors WHERE line_pending_user_id IS NOT NULL')->fetchColumn();
        if ($linkRequests > 0) {
            $items[] = self::item('line_requests', 'warn', $linkRequests, __('attention.line_requests', ['count' => (string) $linkRequests]), 'admin/vendors');
        }

        $deletions = (int) $pdo->query('SELECT COUNT(*) FROM vendors WHERE deletion_requested_at IS NOT NULL')->fetchColumn();
        if ($deletions > 0) {
            $items[] = self::item('deletion_requests', 'danger', $deletions, __('attention.deletion_requests', ['count' => (string) $deletions]), 'admin/vendors');
        }

        $awaiting = (int) $pdo->query("SELECT COUNT(*) FROM lots WHERE status = 'reserved' AND reserved_confirmed_at IS NULL AND deleted_at IS NULL")->fetchColumn();
        if ($awaiting > 0) {
            $items[] = self::item('reserved_waiting', 'info', $awaiting, __('attention.reserved_waiting', ['count' => (string) $awaiting]), 'admin/events');
        }

        $closing = (int) $pdo->query(
            'SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND is_published = 1
             AND booking_close_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)'
        )->fetchColumn();
        if ($closing > 0) {
            $items[] = self::item('closing_soon', 'info', $closing, __('attention.closing_soon', ['count' => (string) $closing]), 'admin/events');
        }

        $unread = (int) $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
        if ($unread > 0) {
            $items[] = self::item('unread_messages', 'info', $unread, __('attention.unread_messages', ['count' => (string) $unread]), 'admin/contacts');
        }

        if (Auth::isSuperAdmin()) {
            $items = array_merge($items, self::systemItems());
        }

        return $items;
    }

    /** Backup freshness, Google Drive trouble and photos still waiting to be resized. */
    private static function systemItems(): array
    {
        $items = [];

        $latest = BackupService::list()[0]['created_at'] ?? null;
        if ($latest === null) {
            $items[] = self::item('backup', 'danger', 0, __('attention.backup_none'), 'admin/backups');
        } elseif (time() - $latest > self::BACKUP_STALE_HOURS * 3600) {
            $hours = (int) floor((time() - $latest) / 3600);
            $items[] = self::item('backup', 'danger', $hours, __('attention.backup_stale', ['hours' => (string) $hours]), 'admin/backups');
        }

        $settings = Setting::get(true);
        if (!empty($settings['gdrive_last_error'])) {
            $items[] = self::item('drive', 'danger', 1, __('attention.drive_error'), 'admin/backups');
        }

        $images = ImageOptimizerService::pendingTotal();
        if ($images > 0) {
            $items[] = self::item('images', 'info', $images, __('attention.images_pending', ['count' => (string) $images]), 'admin/gallery');
        }

        return $items;
    }

    private static function item(string $key, string $level, int $count, string $label, string $path): array
    {
        return ['key' => $key, 'level' => $level, 'count' => $count, 'label' => $label, 'url' => base_url($path)];
    }
}
