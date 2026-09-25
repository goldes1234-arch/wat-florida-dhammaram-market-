<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\Advertisement;

class AdvertisementController
{
    public function index(Request $request): void
    {
        View::render('admin/advertisements/index', [
            'title' => __('nav.advertisements'),
            'active' => 'advertisements',
            'advertisements' => Advertisement::all(),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        $businessName = $request->trimmed('business_name');
        $linkUrl = $request->trimmed('link_url');
        $photoFile = $request->file('image');

        if ($businessName === '' || !$photoFile) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/advertisements');
        }

        if ($linkUrl !== '' && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
            Flash::error(__('settings.ads_invalid_link'));
            redirect('admin/advertisements');
        }

        $error = null;
        $path = Upload::storeImage($photoFile, 'ads', $error);
        if (!$path) {
            Flash::error($error);
            redirect('admin/advertisements');
        }

        Advertisement::create($businessName, $path, $linkUrl ?: null);
        Flash::success(__('settings.ads_added'));
        redirect('admin/advertisements');
    }

    public function destroy(Request $request, string $id): void
    {
        $ad = Advertisement::find((int) $id);
        if ($ad) {
            Upload::delete($ad['image_path']);
            Advertisement::delete((int) $id);
            Flash::success(__('settings.ads_removed'));
        }
        redirect('admin/advertisements');
    }
}
