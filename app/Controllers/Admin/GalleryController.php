<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\GalleryPhoto;

/** The homepage "atmosphere photos" gallery — split out of Settings into its own page. */
class GalleryController
{
    public function index(Request $request): void
    {
        View::render('admin/gallery/index', [
            'title' => __('nav.gallery'),
            'active' => 'gallery',
            'galleryPhotos' => GalleryPhoto::all(),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        $photoFile = $request->file('photo');
        if (!$photoFile) {
            Flash::error(__('validation.generic_error'));
            redirect('admin/gallery');
        }

        $error = null;
        $path = Upload::storeImage($photoFile, 'gallery', $error);
        if (!$path) {
            Flash::error($error);
            redirect('admin/gallery');
        }

        GalleryPhoto::create($path, $request->trimmed('caption'));
        Flash::success(__('settings.gallery_photo_added'));
        redirect('admin/gallery');
    }

    public function destroy(Request $request, string $id): void
    {
        $photo = GalleryPhoto::find((int) $id);
        if ($photo) {
            Upload::delete($photo['image_path']);
            GalleryPhoto::delete((int) $id);
            Flash::success(__('settings.gallery_photo_removed'));
        }
        redirect('admin/gallery');
    }
}
