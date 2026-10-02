<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Services\BackupService;
use App\Services\MigrationService;

class BackupController
{
    public function index(Request $request): void
    {
        View::render('admin/backups/index', [
            'title' => __('backup.title'),
            'active' => 'backups',
            'backups' => BackupService::list(),
            'migrations' => MigrationService::appliedHistory(),
        ], 'admin');
    }

    public function rollbackLastMigration(Request $request): void
    {
        [$ok, $result] = MigrationService::rollbackLast();
        if ($ok) {
            ActivityLog::record('migration.rollback', 'migration', null, __('activity.migration_rolled_back', ['filename' => $result]));
            Flash::success(__('backup.migration_rollback_success', ['filename' => $result]));
        } else {
            Flash::error(__('backup.migration_rollback_failed', ['error' => $result]));
        }
        redirect('admin/backups');
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

    public function verify(Request $request, string $filename): void
    {
        [$ok, $message] = BackupService::verify($filename);
        if ($ok) {
            Flash::success(__('backup.verify_success', ['message' => $message]));
        } else {
            Flash::error(__('backup.verify_failed', ['error' => $message]));
        }
        redirect('admin/backups');
    }

    /**
     * Overwrites the live database — the form makes the admin type the exact filename as a
     * confirmation, but that's only ever enforced client-side by the page's own JS, so it's
     * re-checked here too before anything destructive runs.
     */
    public function restore(Request $request, string $filename): void
    {
        if ($request->trimmed('confirm_filename') !== $filename) {
            Flash::error(__('backup.restore_confirm_mismatch'));
            redirect('admin/backups');
        }

        [$ok, $result] = BackupService::restore($filename);
        if ($ok) {
            ActivityLog::record('backup.restore', 'backup', null, __('activity.backup_restored', ['filename' => $filename]));
            Flash::success(__('backup.restore_success', ['filename' => $filename]));
        } else {
            Flash::error(__('backup.restore_failed', ['error' => $result]));
        }
        redirect('admin/backups');
    }
}
