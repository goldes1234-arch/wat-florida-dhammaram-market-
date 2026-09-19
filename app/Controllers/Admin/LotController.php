<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\Event;
use App\Models\Lot;
use App\Models\Zone;
use App\Support\Validator;

/**
 * Zone selects on every lot-creation form only ever list zones for the current
 * event, but a crafted request could still submit an arbitrary zone_id — this
 * controller always re-validates it belongs to the target event before saving.
 */

class LotController
{
    public function index(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        View::render('admin/lots/index', [
            'title' => __('lot.list_title'),
            'active' => 'events',
            'event' => $event,
            'lots' => Lot::forEvent((int) $eventId),
            'zones' => Zone::forEvent((int) $eventId),
        ], 'admin');
    }

    public function store(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $code = $request->trimmed('code');
        $price = $request->input('price', 0);
        $zoneId = $this->resolveZoneId($request, (int) $eventId);
        $gridRow = $request->input('grid_row') !== '' && $request->input('grid_row') !== null ? (int) $request->input('grid_row') : null;
        $gridCol = $request->input('grid_col') !== '' && $request->input('grid_col') !== null ? (int) $request->input('grid_col') : null;

        if (!Validator::required($code) || !Validator::positiveNumber($price)) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/events/' . $eventId . '/lots');
        }

        if (Lot::codeExists((int) $eventId, $code)) {
            Flash::error(__('lot.code_taken'));
            redirect('admin/events/' . $eventId . '/lots');
        }

        $lotId = Lot::create((int) $eventId, $zoneId, $code, (float) $price, $gridRow, $gridCol);

        $photoFile = $request->file('photo');
        if ($photoFile) {
            $error = null;
            $path = Upload::storeImage($photoFile, 'lots/' . $eventId, $error);
            if ($path) {
                Lot::setPhoto($lotId, $path);
            } else {
                Flash::error($error);
            }
        }

        Flash::success(__('lot.created_success'));
        redirect('admin/events/' . $eventId . '/lots');
    }

    public function bulkStore(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $prefix = $request->trimmed('prefix');
        $start = (int) $request->input('start_number', 0);
        $end = (int) $request->input('end_number', 0);
        $price = $request->input('price', 0);
        $zoneId = $this->resolveZoneId($request, (int) $eventId);

        if ($start < 1 || $end < $start || ($end - $start) > 200 || !Validator::positiveNumber($price)) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/events/' . $eventId . '/lots');
        }

        $padLength = max(2, strlen((string) $end));
        $created = 0;
        $skipped = 0;
        for ($n = $start; $n <= $end; $n++) {
            $code = $prefix . str_pad((string) $n, $padLength, '0', STR_PAD_LEFT);
            if (!Lot::codeExists((int) $eventId, $code)) {
                Lot::create((int) $eventId, $zoneId, $code, (float) $price);
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->flashCreationResult($created, $skipped);
        redirect('admin/events/' . $eventId . '/lots');
    }

    public function gridStore(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $prefix = $request->trimmed('grid_prefix');
        $rows = (int) $request->input('rows', 0);
        $columns = (int) $request->input('columns', 0);
        $price = $request->input('price', 0);
        $zoneId = $this->resolveZoneId($request, (int) $eventId);

        if ($rows < 1 || $columns < 1 || ($rows * $columns) > 500 || !Validator::positiveNumber($price)) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/events/' . $eventId . '/lots');
        }

        $created = 0;
        $skipped = 0;
        for ($r = 1; $r <= $rows; $r++) {
            for ($c = 1; $c <= $columns; $c++) {
                $code = $prefix . $r . '-' . $c;
                if (!Lot::codeExists((int) $eventId, $code)) {
                    Lot::create((int) $eventId, $zoneId, $code, (float) $price, $r, $c);
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->flashCreationResult($created, $skipped);
        redirect('admin/events/' . $eventId . '/lots');
    }

    public function edit(Request $request, string $id): void
    {
        $lot = Lot::find((int) $id);
        if (!$lot) {
            redirect('admin/events');
        }

        View::render('admin/lots/edit', [
            'title' => __('lot.singular'),
            'active' => 'events',
            'lot' => $lot,
            'zones' => Zone::forEvent((int) $lot['event_id']),
        ], 'admin');
    }

    public function update(Request $request, string $id): void
    {
        $lot = Lot::find((int) $id);
        if (!$lot) {
            redirect('admin/events');
        }

        $code = $request->trimmed('code');
        $price = $request->input('price', 0);
        $zoneId = $this->resolveZoneId($request, (int) $lot['event_id']);
        $gridRow = $request->input('grid_row') !== '' && $request->input('grid_row') !== null ? (int) $request->input('grid_row') : null;
        $gridCol = $request->input('grid_col') !== '' && $request->input('grid_col') !== null ? (int) $request->input('grid_col') : null;

        if (!Validator::required($code) || !Validator::positiveNumber($price)) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/lots/' . $id . '/edit');
        }

        if (Lot::codeExists((int) $lot['event_id'], $code, (int) $id)) {
            Flash::error(__('lot.code_taken'));
            redirect('admin/lots/' . $id . '/edit');
        }

        Lot::update((int) $id, $zoneId, $code, (float) $price, $gridRow, $gridCol);

        $photoFile = $request->file('photo');
        if ($photoFile) {
            $error = null;
            $path = Upload::storeImage($photoFile, 'lots/' . $lot['event_id'], $error);
            if ($path) {
                Upload::delete($lot['photo']);
                Lot::setPhoto((int) $id, $path);
            } else {
                Flash::error($error);
            }
        } elseif ($request->input('remove_photo')) {
            Upload::delete($lot['photo']);
            Lot::setPhoto((int) $id, null);
        }

        Flash::success(__('lot.updated_success'));
        redirect('admin/lots/' . $id . '/edit');
    }

    public function toggleDisable(Request $request, string $id): void
    {
        $lot = Lot::find((int) $id);
        if (!$lot) {
            redirect('admin/events');
        }

        if ($lot['status'] === 'disabled') {
            Lot::setStatus((int) $id, 'available');
            Flash::success(__('lot.enabled_success'));
        } elseif ($lot['status'] === 'available') {
            Lot::setStatus((int) $id, 'disabled');
            Flash::success(__('lot.disabled_success'));
        } else {
            Flash::error(__('lot.cannot_disable_active'));
        }

        redirect('admin/lots/' . $id . '/edit');
    }

    public function destroy(Request $request, string $id): void
    {
        $lot = Lot::find((int) $id);
        if (!$lot) {
            redirect('admin/events');
        }

        if (!in_array($lot['status'], ['available', 'disabled'], true)) {
            Flash::error(__('lot.cannot_delete_active'));
            redirect('admin/events/' . $lot['event_id'] . '/lots');
        }

        Lot::softDelete((int) $id);
        ActivityLog::record('lot.delete', 'lot', (int) $id, __('activity.lot_deleted', ['code' => $lot['code']]));
        Flash::success(__('lot.deleted_success'));
        redirect('admin/events/' . $lot['event_id'] . '/lots');
    }

    public function destroySelected(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $ids = $request->post['ids'] ?? [];
        $result = Lot::softDeleteMany(is_array($ids) ? $ids : []);

        if ($result['deleted'] === 0 && $result['skipped'] === 0) {
            Flash::error(__('lot.delete_selected_none'));
        } elseif ($result['skipped'] > 0) {
            Flash::success(__('lot.deleted_selected_partial', [
                'deleted' => $result['deleted'],
                'skipped' => $result['skipped'],
            ]));
        } else {
            Flash::success(__('lot.deleted_selected_success', ['count' => $result['deleted']]));
        }

        if ($result['deleted'] > 0) {
            ActivityLog::record('lot.delete_selected', 'event', (int) $eventId, __('activity.lots_deleted_selected', [
                'count' => $result['deleted'],
                'event' => $event['name_th'],
            ]));
        }

        redirect('admin/events/' . $eventId . '/lots');
    }

    public function destroyAll(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        $result = Lot::softDeleteAllForEvent((int) $eventId);

        if ($result['deleted'] === 0 && $result['skipped'] === 0) {
            Flash::error(__('lot.deleted_all_none'));
        } elseif ($result['skipped'] > 0) {
            Flash::success(__('lot.deleted_all_partial', [
                'deleted' => $result['deleted'],
                'skipped' => $result['skipped'],
            ]));
        } else {
            Flash::success(__('lot.deleted_all_success', ['count' => $result['deleted']]));
        }

        if ($result['deleted'] > 0) {
            ActivityLog::record('lot.delete_all', 'event', (int) $eventId, __('activity.lots_deleted_all', [
                'count' => $result['deleted'],
                'event' => $event['name_th'],
            ]));
        }

        redirect('admin/events/' . $eventId . '/lots');
    }

    /** Returns a validated zone id (must belong to $eventId) or null — silently drops any mismatch. */
    private function resolveZoneId(Request $request, int $eventId): ?int
    {
        $zoneId = $request->input('zone_id');
        if (!$zoneId) {
            return null;
        }
        return Zone::belongsToEvent((int) $zoneId, $eventId) ? (int) $zoneId : null;
    }

    private function flashCreationResult(int $created, int $skipped): void
    {
        if ($skipped > 0) {
            Flash::success(__('lot.bulk_created_success', ['count' => $created]));
            Flash::error(__('lot.bulk_skipped_duplicates', ['count' => $skipped]));
        } else {
            Flash::success(__('lot.bulk_created_success', ['count' => $created]));
        }
    }
}
