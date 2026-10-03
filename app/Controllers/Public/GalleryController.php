<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\GalleryPhoto;

/** The full "photos from our events" page behind the home page's mosaic, optionally narrowed to one event's album. */
class GalleryController
{
    private const PER_PAGE = 24;

    public function index(Request $request): void
    {
        $albums = GalleryPhoto::albums();

        // Only honour an album id that actually has photos; anything else shows everything.
        $eventId = (int) ($request->query['event'] ?? 0);
        if (!in_array($eventId, array_map('intval', array_column($albums, 'id')), true)) {
            $eventId = 0;
        }
        $filter = $eventId ?: null;

        $total = GalleryPhoto::count($filter);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) ($request->query['page'] ?? 1)), $totalPages);

        View::render('public/gallery/index', [
            'title' => __('public.gallery_title'),
            'photos' => GalleryPhoto::page($page, self::PER_PAGE, $filter),
            'albums' => $albums,
            'eventId' => $eventId,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ], 'public');
    }
}
