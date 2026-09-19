<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\Event;
use App\Models\InterestSubscriber;
use App\Models\WaitlistEntry;

class SubscriberController
{
    public function index(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        View::render('admin/subscribers/index', [
            'title' => __('subscriber.title'),
            'active' => 'events',
            'event' => $event,
            'subscribers' => InterestSubscriber::forEvent((int) $eventId),
            'waitlistEntries' => WaitlistEntry::forEvent((int) $eventId),
        ], 'admin');
    }
}
