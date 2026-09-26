<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\Advertisement;
use App\Models\Booking;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Models\Lot;
use App\Models\Setting;
use App\Services\EventStatusService;
use App\Services\StripeService;

class HomeController
{
    public function index(Request $request): void
    {
        $events = Event::publishedForPublic();

        foreach ($events as $event) {
            EventStatusService::maybeNotifyIfJustOpened($event);
        }

        $lotCounts = Lot::availableCountsByEvent();

        $featuredEvent = null;
        $gridEvents = $events;
        if (count($events) >= 2) {
            $openEvents = array_values(array_filter($events, static function (array $e) {
                return EventStatusService::compute($e) === EventStatusService::OPEN;
            }));
            usort($openEvents, static function (array $a, array $b) {
                return strtotime($a['booking_close_at']) <=> strtotime($b['booking_close_at']);
            });
            if ($openEvents) {
                $featuredEvent = $openEvents[0];
                $gridEvents = array_values(array_filter($events, static function (array $e) use ($featuredEvent) {
                    return (int) $e['id'] !== (int) $featuredEvent['id'];
                }));
            }
        }

        View::render('public/home/index', [
            'title' => __('nav.home'),
            'events' => $events,
            'gridEvents' => $gridEvents,
            'featuredEvent' => $featuredEvent,
            'galleryPhotos' => GalleryPhoto::all(),
            'advertisements' => Advertisement::approved(),
            'lotCounts' => $lotCounts,
            'statEventsCount' => count($events),
            'statAvailableLots' => array_sum(array_column($lotCounts, 'available')),
            'statBookedCount' => Booking::bookedCount(),
            'settings' => Setting::get(),
            'stripeEnabled' => StripeService::isEnabled(),
        ], 'public');
    }
}
