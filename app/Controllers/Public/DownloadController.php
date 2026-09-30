<?php

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\View;
use App\Models\DownloadCategory;
use App\Models\DownloadFile;

class DownloadController
{
    public function show(Request $request, string $id): void
    {
        $category = DownloadCategory::find((int) $id);
        if (!$category) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }

        View::render('public/downloads/show', [
            'title' => $category['name'],
            'category' => $category,
            'files' => DownloadFile::forCategory((int) $id),
        ], 'public');
    }
}
