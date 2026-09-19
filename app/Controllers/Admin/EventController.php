<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\Event;
use App\Models\EventContact;
use App\Models\EventPhoto;
use App\Support\Validator;

class EventController
{
    public function index(Request $request): void
    {
        View::render('admin/events/index', [
            'title' => __('event.list_title'),
            'active' => 'events',
            'events' => Event::allForAdmin(),
        ], 'admin');
    }

    public function create(Request $request): void
    {
        View::render('admin/events/create', [
            'title' => __('event.create_title'),
            'active' => 'events',
            'contacts' => [],
            'copyableEvents' => $this->copyableEvents(null),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        [$data, $errors] = $this->validateInput($request);
        [$contacts, $contactErrors] = $this->validateContacts($request);
        $errors = array_merge($errors, $contactErrors);

        if ($errors) {
            Flash::error(implode(' ', $errors));
            Flash::setOld($request->post);
            redirect('admin/events/create');
        }

        $data['slug'] = $this->uniqueSlug($data['name_th'], $data['start_date']);
        $data['created_by'] = Auth::user()['id'] ?? null;

        $this->applyUploads($request, $data, null);

        $id = Event::create($data);
        EventContact::replaceForEvent($id, $contacts);
        Flash::success(__('event.created_success'));
        redirect('admin/events/' . $id . '/edit');
    }

    public function edit(Request $request, string $id): void
    {
        $event = Event::find((int) $id);
        if (!$event) {
            Flash::error(__('booking.not_found'));
            redirect('admin/events');
        }

        View::render('admin/events/edit', [
            'title' => __('event.edit_title'),
            'active' => 'events',
            'event' => $event,
            'contacts' => EventContact::forEvent((int) $id),
            'copyableEvents' => $this->copyableEvents((int) $id),
            'eventPhotos' => EventPhoto::forEvent((int) $id),
        ], 'admin');
    }

    public function storePhoto(Request $request, string $id): void
    {
        $event = Event::find((int) $id);
        if (!$event) {
            redirect('admin/events');
        }

        if (EventPhoto::countForEvent((int) $id) >= EventPhoto::MAX_PER_EVENT) {
            Flash::error(__('event.photo_limit_reached', ['max' => EventPhoto::MAX_PER_EVENT]));
            redirect('admin/events/' . $id . '/edit');
        }

        $photoFile = $request->file('photo');
        if (!$photoFile) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/events/' . $id . '/edit');
        }

        $error = null;
        $path = Upload::storeImage($photoFile, 'events', $error);
        if (!$path) {
            Flash::error($error);
            redirect('admin/events/' . $id . '/edit');
        }

        EventPhoto::create((int) $id, $path);
        Flash::success(__('event.photo_added'));
        redirect('admin/events/' . $id . '/edit');
    }

    public function destroyPhoto(Request $request, string $id, string $photoId): void
    {
        $photo = EventPhoto::find((int) $photoId);
        if ($photo && (int) $photo['event_id'] === (int) $id) {
            Upload::delete($photo['image_path']);
            EventPhoto::delete((int) $photoId);
            Flash::success(__('event.photo_removed'));
        }
        redirect('admin/events/' . $id . '/edit');
    }

    public function contactsJson(Request $request, string $id): void
    {
        header('Content-Type: application/json');
        echo json_encode(['contacts' => EventContact::forEvent((int) $id)]);
    }

    public function update(Request $request, string $id): void
    {
        $event = Event::find((int) $id);
        if (!$event) {
            redirect('admin/events');
        }

        [$data, $errors] = $this->validateInput($request);
        [$contacts, $contactErrors] = $this->validateContacts($request);
        $errors = array_merge($errors, $contactErrors);

        if ($errors) {
            Flash::error(implode(' ', $errors));
            redirect('admin/events/' . $id . '/edit');
        }

        $this->applyUploads($request, $data, $event);

        Event::update((int) $id, $data);
        EventContact::replaceForEvent((int) $id, $contacts);
        Flash::success(__('event.updated_success'));
        redirect('admin/events/' . $id . '/edit');
    }

    public function destroy(Request $request, string $id): void
    {
        $event = Event::find((int) $id);

        Event::softDelete((int) $id);

        if ($event) {
            ActivityLog::record('event.delete', 'event', (int) $id, __('activity.event_deleted', ['name' => $event['name_th']]));
        }

        Flash::success(__('event.deleted_success'));
        redirect('admin/events');
    }

    private function applyUploads(Request $request, array &$data, ?array $existing): void
    {
        $bannerFile = $request->file('banner_image');
        if ($bannerFile) {
            $error = null;
            $path = Upload::storeImage($bannerFile, 'events', $error);
            if ($path) {
                if ($existing) {
                    Upload::delete($existing['banner_image']);
                }
                $data['banner_image'] = $path;
            } else {
                Flash::error($error);
            }
        } elseif ($existing && $request->input('remove_banner_image')) {
            Upload::delete($existing['banner_image']);
            $data['banner_image'] = null;
        }

        $floorplanFile = $request->file('floorplan_image');
        if ($floorplanFile) {
            $error = null;
            $path = Upload::storeImage($floorplanFile, 'events', $error);
            if ($path) {
                if ($existing) {
                    Upload::delete($existing['floorplan_image']);
                }
                $data['floorplan_image'] = $path;
            } else {
                Flash::error($error);
            }
        } elseif ($existing && $request->input('remove_floorplan_image')) {
            Upload::delete($existing['floorplan_image']);
            $data['floorplan_image'] = null;
        }
    }

    private function validateInput(Request $request): array
    {
        $data = [
            'name_th' => $request->trimmed('name_th'),
            'name_en' => $request->trimmed('name_en'),
            'description_th' => $request->trimmed('description_th'),
            'description_en' => $request->trimmed('description_en'),
            'venue_name' => $request->trimmed('venue_name'),
            'start_date' => $request->trimmed('start_date'),
            'end_date' => $request->trimmed('end_date'),
            'booking_open_at' => str_replace('T', ' ', $request->trimmed('booking_open_at')),
            'booking_close_at' => str_replace('T', ' ', $request->trimmed('booking_close_at')),
            'is_published' => $request->input('is_published') ? 1 : 0,
        ];

        $errors = [];

        if (!Validator::required($data['name_th'])) {
            $errors[] = __('validation.required', ['field' => __('event.name_th')]);
        }
        if (!Validator::date($data['start_date']) || !Validator::date($data['end_date'])) {
            $errors[] = __('validation.invalid_date');
        } elseif ($data['end_date'] < $data['start_date']) {
            $errors[] = __('event.dates_invalid');
        }
        if (!Validator::required($data['booking_open_at']) || strtotime($data['booking_open_at']) === false) {
            $errors[] = __('validation.invalid_date');
        }
        if (!Validator::required($data['booking_close_at']) || strtotime($data['booking_close_at']) === false) {
            $errors[] = __('validation.invalid_date');
        }
        if (!$errors && strtotime($data['booking_close_at']) < strtotime($data['booking_open_at'])) {
            $errors[] = __('event.booking_window_invalid');
        }

        return [$data, $errors];
    }

    private function copyableEvents(?int $excludeId): array
    {
        $idsWithContacts = EventContact::eventIdsWithContacts();
        return array_values(array_filter(Event::allForAdmin(), static function (array $e) use ($idsWithContacts, $excludeId) {
            return in_array((int) $e['id'], $idsWithContacts, true) && (int) $e['id'] !== $excludeId;
        }));
    }

    private function validateContacts(Request $request): array
    {
        $names = $request->post['contact_name'] ?? [];
        $phones = $request->post['contact_phone'] ?? [];
        $channels = $request->post['contact_channel'] ?? [];

        $contacts = [];
        $errors = [];

        for ($i = 0; $i < 4; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $phone = trim((string) ($phones[$i] ?? ''));
            $channel = trim((string) ($channels[$i] ?? ''));

            if ($name === '' && $phone === '' && $channel === '') {
                continue;
            }
            if ($name === '' || $phone === '') {
                $errors[] = __('event.contact_incomplete', ['row' => $i + 1]);
                continue;
            }

            $contacts[] = ['name' => $name, 'phone' => $phone, 'contact_channel' => $channel];
        }

        return [$contacts, $errors];
    }

    private function uniqueSlug(string $name, string $dateSeed = ''): string
    {
        $base = $this->slugify($name, $dateSeed);
        $slug = $base;
        $i = 2;
        while (Event::slugExists($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function slugify(string $text, string $dateSeed = ''): string
    {
        $ascii = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
        if ($ascii === '') {
            $prefix = $dateSeed ? trim(preg_replace('/[^0-9]/', '-', $dateSeed), '-') : 'event';
            $ascii = $prefix . '-' . bin2hex(random_bytes(3));
        }
        return $ascii;
    }
}
