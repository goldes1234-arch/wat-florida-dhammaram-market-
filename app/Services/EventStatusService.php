<?php

namespace App\Services;

class EventStatusService
{
    public const COMING_SOON = 'coming_soon';
    public const OPEN = 'open';
    public const CLOSED = 'closed';

    public static function compute(array $event): string
    {
        $now = time();
        $opensAt = strtotime($event['booking_open_at']);
        $closesAt = strtotime($event['booking_close_at']);

        if ($now < $opensAt) {
            return self::COMING_SOON;
        }
        if ($now > $closesAt) {
            return self::CLOSED;
        }
        return self::OPEN;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::COMING_SOON => __('event.status_coming_soon'),
            self::OPEN => __('event.status_open'),
            self::CLOSED => __('event.status_closed'),
            default => $status,
        };
    }

    public static function badgeClass(string $status): string
    {
        return match ($status) {
            self::COMING_SOON => 'badge badge-amber',
            self::OPEN => 'badge badge-green',
            self::CLOSED => 'badge badge-slate',
            default => 'badge',
        };
    }

    /**
     * Lazily fires the "booking is now open" email the first time this event is
     * viewed after crossing into OPEN status — there's no cron/queue in this app,
     * so ordinary public traffic is what drives the check. open_notified_at makes
     * it idempotent (and a cheap no-op on every call after the first).
     */
    public static function maybeNotifyIfJustOpened(array $event): void
    {
        if (!empty($event['open_notified_at'])) {
            return;
        }
        if (self::compute($event) !== self::OPEN) {
            return;
        }
        NotificationService::notifyEventOpen($event);
        \App\Models\Event::markOpenNotified((int) $event['id']);
    }
}
