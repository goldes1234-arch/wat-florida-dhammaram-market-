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

    public function mapEditor(Request $request, string $eventId): void
    {
        $event = Event::find((int) $eventId);
        if (!$event) {
            redirect('admin/events');
        }

        View::render('admin/lots/map', [
            'title' => __('lot.map_editor_title'),
            'active' => 'events',
            'event' => $event,
            'lots' => Lot::forEvent((int) $eventId),
        ], 'admin');
    }

    public function savePosition(Request $request, string $eventId): void
    {
        header('Content-Type: application/json');

        $event = Event::find((int) $eventId);
        $lot = Lot::find((int) $request->input('lot_id'));
        $x = $request->input('map_x');
        $y = $request->input('map_y');

        if (!$event || !$lot || (int) $lot['event_id'] !== (int) $eventId
            || !is_numeric($x) || !is_numeric($y) || $x < 0 || $x > 100 || $y < 0 || $y > 100) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        Lot::setMapPosition((int) $lot['id'], (float) $x, (float) $y);
        echo json_encode(['ok' => true]);
    }

    public function saveSize(Request $request, string $eventId): void
    {
        header('Content-Type: application/json');

        $event = Event::find((int) $eventId);
        $lot = Lot::find((int) $request->input('lot_id'));
        $size = $request->input('map_size');

        if (!$event || !$lot || (int) $lot['event_id'] !== (int) $eventId
            || !in_array($size, ['small', 'medium', 'large'], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        Lot::setMapSize((int) $lot['id'], $size);
        echo json_encode(['ok' => true]);
    }

    public function saveShape(Request $request, string $eventId): void
    {
        header('Content-Type: application/json');

        $event = Event::find((int) $eventId);
        $lot = Lot::find((int) $request->input('lot_id'));
        $shape = $request->input('map_shape');

        if (!$event || !$lot || (int) $lot['event_id'] !== (int) $eventId
            || !in_array($shape, ['pin', 'box'], true)) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        Lot::setMapShape((int) $lot['id'], $shape);
        echo json_encode(['ok' => true]);
    }

    public function saveRotation(Request $request, string $eventId): void
    {
        header('Content-Type: application/json');

        $event = Event::find((int) $eventId);
        $lot = Lot::find((int) $request->input('lot_id'));
        $rotation = $request->input('map_rotation');

        if (!$event || !$lot || (int) $lot['event_id'] !== (int) $eventId
            || !is_numeric($rotation) || $rotation < -180 || $rotation > 180) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        Lot::setMapRotation((int) $lot['id'], (float) $rotation);
        echo json_encode(['ok' => true]);
    }

    public function mapQuickAdd(Request $request, string $eventId): void
    {
        header('Content-Type: application/json');

        $event = Event::find((int) $eventId);
        if (!$event) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        $code = $request->trimmed('code');
        $price = $request->input('price', 0);

        if (!Validator::required($code) || !Validator::positiveNumber($price)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => __('validation.generic_error')]);
            return;
        }

        if (Lot::codeExists((int) $eventId, $code)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => __('lot.code_taken')]);
            return;
        }

        $lotId = Lot::create((int) $eventId, null, $code, (float) $price);
        $lot = Lot::find($lotId);

        echo json_encode([
            'ok' => true,
            'lot' => [
                'id' => $lot['id'],
                'code' => $lot['code'],
                'map_size' => $lot['map_size'],
                'map_shape' => $lot['map_shape'],
            ],
        ]);
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

    // Lets the lot list table save a single cell (code / zone / price) in place —
    // fetches the current row and re-saves it through the same Lot::update() the
    // full edit form uses, only swapping in the one changed field, so validation
    // and the code-uniqueness check stay identical either way.
    public function inlineUpdate(Request $request, string $id): void
    {
        header('Content-Type: application/json');

        $lot = Lot::find((int) $id);
        if (!$lot) {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        $field = $request->input('field');
        $value = $request->trimmed('value');

        $code = $lot['code'];
        $price = (float) $lot['price'];
        $zoneId = $lot['zone_id'] !== null ? (int) $lot['zone_id'] : null;

        if ($field === 'code') {
            if (!Validator::required($value)) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => __('validation.generic_error')]);
                return;
            }
            if (Lot::codeExists((int) $lot['event_id'], $value, (int) $id)) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => __('lot.code_taken')]);
                return;
            }
            $code = $value;
        } elseif ($field === 'price') {
            if (!Validator::positiveNumber($value)) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => __('validation.generic_error')]);
                return;
            }
            $price = (float) $value;
        } elseif ($field === 'zone_id') {
            if ($value === '') {
                $zoneId = null;
            } elseif (Zone::belongsToEvent((int) $value, (int) $lot['event_id'])) {
                $zoneId = (int) $value;
            } else {
                http_response_code(422);
                echo json_encode(['ok' => false]);
                return;
            }
        } else {
            http_response_code(422);
            echo json_encode(['ok' => false]);
            return;
        }

        $gridRow = $lot['grid_row'] !== null ? (int) $lot['grid_row'] : null;
        $gridCol = $lot['grid_col'] !== null ? (int) $lot['grid_col'] : null;
        Lot::update((int) $id, $zoneId, $code, $price, $gridRow, $gridCol);

        $updated = Lot::find((int) $id);
        echo json_encode([
            'ok' => true,
            'display' => [
                'code' => $updated['code'],
                'zone_name' => $updated['zone_name'] ?? __('lot.no_zone'),
                'price' => money((float) $updated['price']),
            ],
        ]);
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
