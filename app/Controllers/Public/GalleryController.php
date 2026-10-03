<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\GalleryPhoto;

/** The full "photos from our events" page behind the home page's mosaic. */
class GalleryController
{
    private const PER_PAGE = 24;

    public function index(Request $request): void
    {
        $total = GalleryPhoto::count();
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) ($request->query['page'] ?? 1)), $totalPages);

        View::render('public/gallery/index', [
            'title' => __('public.gallery_title'),
            'photos' => GalleryPhoto::page($page, self::PER_PAGE),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ], 'public');
    }
}
