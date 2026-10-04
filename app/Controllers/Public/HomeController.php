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
        // Events that have already ended are not offered for booking: they move to a collapsed "past events" list.
        $today = date('Y-m-d');
        $publishedEvents = Event::publishedForPublic();
        $events = array_values(array_filter($publishedEvents, static fn (array $e) => $e['end_date'] >= $today));
        $pastEvents = array_values(array_filter($publishedEvents, static fn (array $e) => $e['end_date'] < $today));

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
            'pastEvents' => $pastEvents,
            'mobileCta' => $events ? [
                'url' => $featuredEvent ? base_url('events/' . $featuredEvent['slug']) : '#events',
                'label' => __('public.hero_cta_book'),
                'watch' => '.hero-actions',
            ] : null,
            'featuredEvent' => $featuredEvent,
            'galleryPhotos' => GalleryPhoto::all(),
            'advertisements' => Advertisement::withSellingAt(Advertisement::approved()),
            'lotCounts' => $lotCounts,
            'statEventsCount' => count($events),
            'statAvailableLots' => array_sum(array_map(static fn (array $e) => (int) ($lotCounts[$e['id']]['available'] ?? 0), $events)),
            'statBookedCount' => Booking::bookedCount(),
            'settings' => Setting::get(),
            'stripeEnabled' => StripeService::isEnabled(),
        ], 'public');
    }
}
