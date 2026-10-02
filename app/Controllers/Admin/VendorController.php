<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Vendor;
use App\Services\LineService;
use App\Support\Validator;

class VendorController
{
    public function index(Request $request): void
    {
        $search = $request->trimmed('q');
        $perPage = 50;

        $total = Vendor::countWithSearch($search ?: null);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($request->query['page'] ?? 1)), $totalPages);

        View::render('admin/vendors/index', [
            'title' => __('nav.vendors'),
            'active' => 'vendors',
            'vendors' => Vendor::allWithBookingCounts($search ?: null, $page, $perPage),
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
        ], 'admin');
    }

    public function store(Request $request): void
    {
        $name = $request->trimmed('name');
        $phone = $request->trimmed('phone');
        $email = $request->trimmed('email');

        if (!Validator::required($name) || !Validator::required($phone) || ($email !== '' && !Validator::email($email))) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/vendors');
        }

        Vendor::create($name, $phone, $email ?: null);
        Flash::success(__('vendor.added_success'));
        redirect('admin/vendors');
    }

    public function show(Request $request, string $id): void
    {
        $vendor = Vendor::find((int) $id);
        if (!$vendor) {
            redirect('admin/vendors');
        }

        View::render('admin/vendors/show', [
            'title' => $vendor['name'],
            'active' => 'vendors',
            'vendor' => $vendor,
            'history' => Vendor::bookingHistory((int) $id),
        ], 'admin');
    }

    public function update(Request $request, string $id): void
    {
        $vendor = Vendor::find((int) $id);
        if (!$vendor) {
            redirect('admin/vendors');
        }

        $name = $request->trimmed('name');
        $phone = $request->trimmed('phone');
        $email = $request->trimmed('email');
        $notes = $request->trimmed('notes');

        if (!Validator::required($name) || !Validator::required($phone) || ($email !== '' && !Validator::email($email))) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/vendors/' . $id);
        }

        Vendor::update((int) $id, $name, $phone, $email ?: null, $notes ?: null);
        Flash::success(__('vendor.updated_success'));
        redirect('admin/vendors/' . $id);
    }

    public function destroy(Request $request, string $id): void
    {
        $vendor = Vendor::find((int) $id);
        if ($vendor) {
            Vendor::delete((int) $id);
            Flash::success(__('vendor.deleted_success'));
        }
        redirect('admin/vendors');
    }

    public function dismissDeletionRequest(Request $request, string $id): void
    {
        $vendor = Vendor::find((int) $id);
        if ($vendor) {
            Vendor::clearDeletionRequest((int) $id);
            Flash::success(__('vendor.dismiss_deletion_request_success'));
        }
        redirect('admin/vendors/' . $id);
    }

    public function sendLineMessage(Request $request, string $id): void
    {
        $vendor = Vendor::find((int) $id);
        $message = $request->trimmed('message');

        if (!$vendor || !$vendor['line_user_id'] || !Validator::required($message)) {
            Flash::error(__('vendor.line_send_failed'));
            redirect('admin/vendors/' . $id);
        }

        if (LineService::push($vendor['line_user_id'], $message)) {
            Flash::success(__('vendor.line_send_success'));
        } else {
            Flash::error(__('vendor.line_send_failed'));
        }

        redirect('admin/vendors/' . $id);
    }
}
