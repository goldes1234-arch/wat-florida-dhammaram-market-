<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Advertisement;
use App\Services\AdvertisementImageService;

class AdvertisementController
{
    public function index(Request $request): void
    {
        View::render('admin/advertisements/index', [
            'title' => __('nav.advertisements'),
            'active' => 'advertisements',
            'advertisements' => Advertisement::approved(),
            'pendingAdvertisements' => Advertisement::pending(),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        $businessName = $request->trimmed('business_name');
        $description = $request->trimmed('description');
        $linkUrl = $request->trimmed('link_url');
        $files = AdvertisementImageService::normalize($request->files['images'] ?? $request->files['image'] ?? null);

        if ($businessName === '' || !$files) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/advertisements');
        }

        if ($linkUrl !== '' && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
            Flash::error(__('settings.ads_invalid_link'));
            redirect('admin/advertisements');
        }

        $error = null;
        $stored = AdvertisementImageService::storeAll($files, Advertisement::MAX_IMAGES, $error);
        if ($stored === null) {
            Flash::error($error);
            redirect('admin/advertisements');
        }

        $cover = array_shift($stored);
        $id = Advertisement::create($businessName, $cover['path'], $linkUrl ?: null, $description ?: null, 'approved', null, null, $cover['thumb']);
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
