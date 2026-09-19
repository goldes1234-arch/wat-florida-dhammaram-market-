<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Event;
use App\Models\Zone;
use App\Support\Validator;

class ZoneController
{
    public function index(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $idsWithZones = Zone::eventIdsWithZones();
        $copyableEvents = array_values(array_filter(Event::allForAdmin(), static function (array $e) use ($idsWithZones, $eventId) {
            return in_array((int) $e['id'], $idsWithZones, true) && (int) $e['id'] !== (int) $eventId;
        }));

        View::render('admin/zones/index', [
            'title' => __('zone.list_title'),
            'active' => 'events',
            'event' => $event,
            'zones' => Zone::forEvent((int) $eventId),
            'copyableEvents' => $copyableEvents,
        ], 'admin');
    }

    public function copyFrom(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $sourceEventId = (int) $request->trimmed('source_event_id');
        if (!$sourceEventId) {
            Flash::error(__('zone.copy_pick_first'));
            redirect('admin/events/' . $eventId . '/zones');
        }

        $result = Zone::copyFromEvent($sourceEventId, (int) $eventId);

        if ($result['copied'] === 0 && $result['skipped'] === 0) {
            Flash::error(__('zone.copy_none'));
        } elseif ($result['skipped'] > 0) {
            Flash::success(__('zone.copy_partial', [
                'copied' => $result['copied'],
                'skipped' => $result['skipped'],
            ]));
        } else {
            Flash::success(__('zone.copy_success', ['count' => $result['copied']]));
        }

        redirect('admin/events/' . $eventId . '/zones');
    }

    public function store(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $name = $request->trimmed('name');
        $price = $request->input('default_price', 0);

        if (!Validator::required($name) || !Validator::positiveNumber($price)) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/events/' . $eventId . '/zones');
        }

        Zone::create((int) $eventId, $name, (float) $price);
        Flash::success(__('zone.created_success'));
        redirect('admin/events/' . $eventId . '/zones');
    }

    public function update(Request $request, string $id): void
    {
        $zone = Zone::find((int) $id);
        if (!$zone) {
            redirect('admin/events');
        }

        $name = $request->trimmed('name');
        $price = $request->input('default_price', 0);
        if (Validator::required($name) && Validator::positiveNumber($price)) {
            Zone::update((int) $id, $name, (float) $price);
        }
        redirect('admin/events/' . $zone['event_id'] . '/zones');
    }

    public function destroy(Request $request, string $id): void
    {
        $zone = Zone::find((int) $id);
        if (!$zone) {
            redirect('admin/events');
        }
        Zone::delete((int) $id);
        Flash::success(__('zone.deleted_success'));
        redirect('admin/events/' . $zone['event_id'] . '/zones');
    }
}
