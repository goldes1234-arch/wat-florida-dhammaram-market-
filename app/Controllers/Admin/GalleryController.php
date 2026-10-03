<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\Event;
use App\Models\GalleryPhoto;
use App\Services\GalleryImageService;
use App\Services\ImageOptimizerService;

/** The "atmosphere photos" gallery: add many at once, caption/album them, and put them in order. */
class GalleryController
{
    public function index(Request $request): void
    {
        View::render('admin/gallery/index', [
            'title' => __('nav.gallery'),
            'active' => 'gallery',
            'galleryPhotos' => $photos = GalleryPhoto::all(),
            'duplicates' => GalleryPhoto::duplicatesAmong($photos),
            'events' => Event::allForAdmin(),
            'pendingImages' => ImageOptimizerService::pendingCounts(),
        ], 'admin');
    }

    /** One photo per request: the admin page uploads a multi-selection one file at a time (XHR), which keeps each request well under PHP's size limits. */
    public function store(Request $request): void
    {
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $error = null;

        $photoFile = $request->file('photo');
        $stored = $photoFile ? GalleryImageService::store($photoFile, 'gallery', $error) : null;

        // The same photo uploaded twice re-encodes to identical bytes: refuse it instead of filling the gallery with copies.
        if ($stored) {
            $hash = GalleryPhoto::fingerprint($stored['path']);
            foreach (GalleryPhoto::all() as $existing) {
                if ($hash !== null && GalleryPhoto::fingerprint($existing['image_path']) === $hash) {
                    Upload::delete($stored['path']);
                    Upload::delete($stored['thumb'] ?? null);
                    $stored = null;
                    $error = __('gallery.duplicate_photo');
                    break;
                }
            }
        }

        if ($stored) {
            GalleryPhoto::create(
                $stored['path'],
                $stored['thumb'],
                mb_substr($request->trimmed('caption'), 0, 150),
                $this->validEventId($request->trimmed('event_id'))
            );
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($stored ? ['ok' => true] : ['ok' => false, 'error' => $error ?: __('validation.generic_error')]);
            return;
        }

        if ($stored) {
            Flash::success(__('settings.gallery_photo_added'));
        } else {
            Flash::error($error ?: __('validation.generic_error'));
        }
        redirect('admin/gallery');
    }

    public function update(Request $request, string $id): void
    {
        if (GalleryPhoto::find((int) $id)) {
            GalleryPhoto::updateDetails(
                (int) $id,
                mb_substr($request->trimmed('caption'), 0, 150),
                $this->validEventId($request->trimmed('event_id'))
            );
            Flash::success(__('gallery.saved'));
        }
        redirect('admin/gallery');
    }

    /** Drag-and-drop on the admin page posts the whole new order as order[]=id&order[]=id… (XHR). */
    public function reorder(Request $request): void
    {
        $order = $request->post['order'] ?? [];
        if (is_array($order) && $order) {
            GalleryPhoto::setOrder($order);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function move(Request $request, string $id): void
    {
        GalleryPhoto::move((int) $id, $request->trimmed('direction') === 'up' ? -1 : 1);
        redirect('admin/gallery');
    }

    public function destroy(Request $request, string $id): void
    {
        $photo = GalleryPhoto::find((int) $id);
        if ($photo) {
            Upload::delete($photo['image_path']);
            Upload::delete($photo['thumb_path'] ?? null);
            GalleryPhoto::delete((int) $id);
            Flash::success(__('settings.gallery_photo_removed'));
        }
        redirect('admin/gallery');
    }

    private function validEventId(string $raw): ?int
    {
        $id = (int) $raw;
        return ($id > 0 && Event::find($id)) ? $id : null;
    }
}
