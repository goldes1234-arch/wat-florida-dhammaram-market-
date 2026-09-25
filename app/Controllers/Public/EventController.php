<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\Advertisement;
use App\Models\Event;
use App\Models\EventContact;
use App\Models\EventPhoto;
use App\Models\Lot;
use App\Models\WaitlistEntry;
use App\Services\EventStatusService;

class EventController
{
    public function show(Request $request, string $slug): void
    {
        $event = Event::findBySlug($slug);
        if (!$event || !$event['is_published']) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        EventStatusService::maybeNotifyIfJustOpened($event);

        $locale = \App\Core\Lang::locale();
        $eventName = $locale === 'en' ? ($event['name_en'] ?: $event['name_th']) : $event['name_th'];
        $eventDescription = $locale === 'en' ? ($event['description_en'] ?: $event['description_th']) : $event['description_th'];
        $metaDescription = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $eventDescription)));
        if (mb_strlen($metaDescription) > 160) {
            $metaDescription = mb_substr($metaDescription, 0, 157) . '...';
        }

        $lots = Lot::forEvent((int) $event['id']);
        $layoutMode = $event['layout_mode'] ?? 'grid';

        $mappedLots = [];
        $photoLots = [];
        $unmappedLots = [];
        $maxRow = 0;
        $maxCol = 0;
        if ($layoutMode === 'photo') {
            foreach ($lots as $lot) {
                if ($lot['map_x'] !== null && $lot['map_y'] !== null) {
                    $photoLots[] = $lot;
                } else {
                    $unmappedLots[] = $lot;
                }
            }
        } else {
            foreach ($lots as $lot) {
                if ($lot['grid_row'] !== null && $lot['grid_col'] !== null) {
                    $mappedLots[] = $lot;
                    $maxRow = max($maxRow, (int) $lot['grid_row']);
                    $maxCol = max($maxCol, (int) $lot['grid_col']);
                } else {
                    $unmappedLots[] = $lot;
                }
            }
        }

        $grouped = [];
        foreach ($unmappedLots as $lot) {
            $key = $lot['zone_id'] ?? 0;
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['zone_name' => $lot['zone_name'] ?? null, 'lots' => []];
            }
            $grouped[$key]['lots'][] = $lot;
        }

        $status = EventStatusService::compute($event);
        $isSoldOut = $status === EventStatusService::OPEN && count($lots) > 0
            && !array_filter($lots, static fn (array $l) => $l['status'] === 'available');

        View::render('public/events/show', [
            'title' => $eventName,
            'event' => $event,
            'status' => $status,
            'groupedLots' => $grouped,
            'mappedLots' => $mappedLots,
            'photoLots' => $photoLots,
            'layoutMode' => $layoutMode,
            'maxRow' => $maxRow,
            'maxCol' => $maxCol,
            'eventContacts' => EventContact::forEvent((int) $event['id']),
            'eventPhotos' => EventPhoto::forEvent((int) $event['id']),
            'advertisements' => Advertisement::all(),
            'isSoldOut' => $isSoldOut,
            'waitlistCount' => $isSoldOut ? count(WaitlistEntry::notNotifiedForEvent((int) $event['id'])) : 0,
            'metaTitle' => $eventName,
            'metaDescription' => $metaDescription ?: __('public.tagline'),
            'metaImage' => !empty($event['banner_image']) ? full_upload_url($event['banner_image']) : null,
            'metaType' => 'article',
        ], 'public');
    }

    /** Lightweight JSON poll so the booth map can reflect other guests' bookings without a full reload. */
    public function lotStatus(Request $request, string $slug): void
    {
        $event = Event::findBySlug($slug);
        header('Content-Type: application/json');

        if (!$event || !$event['is_published']) {
            http_response_code(404);
            echo json_encode(['lots' => []]);
            return;
        }

        echo json_encode(['lots' => Lot::statusMapForEvent((int) $event['id'])]);
    }
}
