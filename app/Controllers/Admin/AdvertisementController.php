<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Advertisement;
use App\Models\Vendor;
use App\Services\AdvertisementImageService;
use App\Support\Validator;

class AdvertisementController
{
    public function index(Request $request): void
    {
        View::render('admin/advertisements/index', [
            'title' => __('nav.advertisements'),
            'active' => 'advertisements',
            'advertisements' => Advertisement::approved(),
            'pendingAdvertisements' => Advertisement::pending(),
            'vendors' => Vendor::options(),
        ], 'admin');
    }

    /**
     * Text fields shared by "add" and "edit". Returns the cleaned values, or null after flashing the first problem.
     * Every link must be a real http(s) URL: it ends up in an href on the public site.
     */
    private function details(Request $request): ?array
    {
        $d = [
            'business_name' => $request->trimmed('business_name'),
            'description' => $request->trimmed('description'),
            'link_url' => $request->trimmed('link_url'),
            'badge' => $request->trimmed('badge'),
            'phone' => $request->trimmed('phone'),
            'map_url' => $request->trimmed('map_url'),
            'line_url' => $request->trimmed('line_url'),
            'vendor_id' => (int) $request->trimmed('vendor_id'),
        ];

        if ($d['business_name'] === '') {
            Flash::error(__('validation.generic_error'));
            return null;
        }
        foreach (['link_url', 'map_url', 'line_url'] as $field) {
            if (!Validator::httpUrl($d[$field])) {
                Flash::error(__('settings.ads_invalid_link'));
                return null;
            }
        }
        if (!Validator::dialable($d['phone'])) {
            Flash::error(__('ads.invalid_phone'));
            return null;
        }
        if ($d['badge'] !== '' && !Validator::inList($d['badge'], Advertisement::BADGES)) {
            $d['badge'] = '';
        }
        if ($d['vendor_id'] > 0 && !Vendor::find($d['vendor_id'])) {
            $d['vendor_id'] = 0;
        }

        return $d;
    }

    public function update(Request $request, string $id): void
    {
        if (!Advertisement::find((int) $id)) {
            redirect('admin/advertisements');
        }
        $d = $this->details($request);
        if ($d === null) {
            redirect('admin/advertisements');
        }

        Advertisement::updateDetails((int) $id, $d);
        Flash::success(__('ads.updated'));
        redirect('admin/advertisements');
    }

    public function store(Request $request): void
    {
        $files = AdvertisementImageService::normalize($request->files['images'] ?? $request->files['image'] ?? null);
        $d = $this->details($request);

        if ($d === null || !$files) {
            if ($d !== null) {
                Flash::error(__('validation.generic_error'));
            }
            redirect('admin/advertisements');
        }

        $error = null;
        $stored = AdvertisementImageService::storeAll($files, Advertisement::MAX_IMAGES, $error);
        if ($stored === null) {
            Flash::error($error);
            redirect('admin/advertisements');
        }

        $cover = array_shift($stored);
        $id = Advertisement::create($d['business_name'], $cover['path'], $d['link_url'], $d['description'], 'approved', null, null, $cover['thumb'], $d);
        foreach ($stored as $extra) {
            Advertisement::addImage($id, $extra['path'], $extra['thumb']);
        }
        Flash::success(__('settings.ads_added'));
        redirect('admin/advertisements');
    }

    /** Adds more photos to an existing shop, up to the per-shop maximum. */
    public function addImages(Request $request, string $id): void
    {
        $ad = Advertisement::find((int) $id);
        if (!$ad) {
            redirect('admin/advertisements');
        }

        $room = Advertisement::roomForImages((int) $id);
        $files = AdvertisementImageService::normalize($request->files['images'] ?? null);
        if (!$files || $room < 1) {
            Flash::error($room < 1 ? __('ads.images_full', ['max' => (string) Advertisement::MAX_IMAGES]) : __('ads.images_required'));
            redirect('admin/advertisements');
        }

        $error = null;
        $stored = AdvertisementImageService::storeAll($files, $room, $error);
        if ($stored === null) {
            Flash::error($error);
            redirect('admin/advertisements');
        }

        foreach ($stored as $extra) {
            Advertisement::addImage((int) $id, $extra['path'], $extra['thumb']);
        }
        Flash::success(__('ads.images_added', ['count' => (string) count($stored)]));
        redirect('admin/advertisements');
    }

    public function destroyImage(Request $request, string $id, string $imageId): void
    {
        $image = Advertisement::findImage((int) $imageId);
        if ($image && (int) $image['advertisement_id'] === (int) $id) {
            AdvertisementImageService::deleteFiles($image['image_path'], $image['thumb_path']);
            Advertisement::deleteImage((int) $imageId);
            Flash::success(__('ads.image_removed'));
        }
        redirect('admin/advertisements');
    }

    public function approve(Request $request, string $id): void
    {
        $ad = Advertisement::find((int) $id);
        if ($ad) {
            Advertisement::approve((int) $id);
            Flash::success(__('settings.ads_approved'));
        }
        redirect('admin/advertisements');
    }

    public function destroy(Request $request, string $id): void
    {
        $ad = Advertisement::find((int) $id);
        if ($ad) {
            foreach (Advertisement::withImages([$ad])[0]['images'] as $image) {
                AdvertisementImageService::deleteFiles($image['path'], $image['thumb'] === $image['path'] ? null : $image['thumb']);
            }
            Advertisement::delete((int) $id);
            Flash::success(__('settings.ads_removed'));
        }
        redirect('admin/advertisements');
    }
}
