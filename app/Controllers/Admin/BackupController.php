<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Services\BackupService;

class BackupController
{
    public function index(Request $request): void
    {
        View::render('admin/backups/index', [
            'title' => __('backup.title'),
            'active' => 'backups',
            'backups' => BackupService::list(),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        [$ok, $result] = BackupService::create();
        if ($ok) {
            Flash::success(__('backup.created_success', ['filename' => $result]));
        } else {
            Flash::error(__('backup.created_failed', ['error' => $result]));
        }
        redirect('admin/backups');
    }

    public function download(Request $request, string $filename): void
    {
        $path = BackupService::path($filename);
        if (!$path) {
            Flash::error(__('backup.not_found'));
            redirect('admin/backups');
        }

        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function destroy(Request $request, string $filename): void
    {
        BackupService::delete($filename);
        ActivityLog::record('backup.delete', 'backup', null, __('activity.backup_deleted', ['filename' => $filename]));
        Flash::success(__('backup.deleted_success'));
        redirect('admin/backups');
    }
}
