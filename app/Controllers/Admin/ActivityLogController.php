<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\AdminActivityLog;

class ActivityLogController
{
    public function index(Request $request): void
    {
        View::render('admin/activity_log/index', [
            'title' => __('activity.title'),
            'active' => 'activity_log',
            'logs' => AdminActivityLog::recent(200),
        ], 'admin');
    }
}
