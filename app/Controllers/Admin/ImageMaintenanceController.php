<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Flash;
use App\Core\Request;
use App\Services\ImageOptimizerService;

/** One-click, repeatable clean-up that slims down photos uploaded before automatic resizing (super_admin only, see routes). */
class ImageMaintenanceController
{
    public function optimize(Request $request): void
    {
        $result = ImageOptimizerService::run();

        if ($result['processed'] > 0) {
            ActivityLog::record(
                'images.optimized', 'image', null,
                __('images.activity_optimized', ['count' => (string) $result['processed'], 'mb' => number_format($result['saved'] / 1048576, 1)])
            );
            Flash::success(__('images.optimize_done', [
                'count' => (string) $result['processed'],
                'mb' => number_format($result['saved'] / 1048576, 1),
                'remaining' => (string) $result['remaining'],
            ]));
        } elseif (!$result['failed']) {
            Flash::success(__('images.nothing_to_do'));
        }
        if ($result['failed']) {
            Flash::error(__('images.optimize_failed', ['files' => implode('; ', array_slice($result['failed'], 0, 3))]));
        }

        redirect('admin/gallery');
    }
}
