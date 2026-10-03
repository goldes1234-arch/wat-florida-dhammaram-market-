<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Session;
use App\Services\BackupService;
use App\Services\GoogleDriveService;

/** Connect / disconnect the temple's Google Drive for off-server backup copies (super_admin only, see routes). */
class GoogleDriveController
{
    public function connect(Request $request): void
    {
        if (!GoogleDriveService::isConfigured()) {
            Flash::error(__('gdrive.not_configured'));
            redirect('admin/backups');
        }

        $state = bin2hex(random_bytes(16));
        Session::put('gdrive_oauth_state', $state);
        header('Location: ' . GoogleDriveService::authUrl($state));
        exit;
    }

    public function callback(Request $request): void
    {
        $expected = (string) Session::get('gdrive_oauth_state', '');
        Session::forget('gdrive_oauth_state');
        $state = (string) ($request->query['state'] ?? '');

        if ($expected === '' || !hash_equals($expected, $state)) {
            Flash::error(__('gdrive.bad_state'));
            redirect('admin/backups');
        }
        if (!empty($request->query['error']) || empty($request->query['code'])) {
            Flash::error(__('gdrive.denied'));
            redirect('admin/backups');
        }

        [$ok, $result] = GoogleDriveService::connect((string) $request->query['code']);
        if ($ok) {
            ActivityLog::record('backup.gdrive_connected', 'backup', null, __('gdrive.activity_connected', ['account' => $result ?: '-']));
            Flash::success(__('gdrive.connected_success', ['account' => $result ?: '']));
        } else {
            Flash::error(__('gdrive.connect_failed', ['error' => $result]));
        }
        redirect('admin/backups');
    }

    public function disconnect(Request $request): void
    {
        GoogleDriveService::disconnect();
        ActivityLog::record('backup.gdrive_disconnected', 'backup', null, __('gdrive.activity_disconnected'));
        Flash::success(__('gdrive.disconnected_success'));
        redirect('admin/backups');
    }

    /** Sends the newest backup to Drive right now, so the admin can check the link works without waiting for 02:00. */
    public function uploadLatest(Request $request): void
    {
        $latest = BackupService::list()[0]['filename'] ?? null;
        if (!$latest) {
            Flash::error(__('gdrive.no_backup_yet'));
            redirect('admin/backups');
        }

        [$ok, $result] = GoogleDriveService::uploadBackup(BackupService::dir() . '/' . $latest, $latest);
        if ($ok) {
            Flash::success(__('gdrive.upload_success', ['filename' => $result]));
        } else {
            Flash::error(__('gdrive.upload_failed', ['error' => $result]));
        }
        redirect('admin/backups');
    }
}
