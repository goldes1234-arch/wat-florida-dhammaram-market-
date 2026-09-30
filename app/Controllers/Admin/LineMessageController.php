<?php

namespace App\Controllers\Admin;

use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\Vendor;
use App\Services\LineService;
use App\Support\Validator;

class LineMessageController
{
    public function index(Request $request): void
    {
        View::render('admin/line_messages/index', [
            'title' => __('line_message.title'),
            'active' => 'line_messages',
            'vendors' => Vendor::linkedToLine(),
            'lineEnabled' => LineService::isEnabled(),
        ], 'admin');
    }

    public function send(Request $request): void
    {
        $message = $request->trimmed('message');
        $vendorIds = array_map('intval', $request->post['vendor_ids'] ?? []);

        if (!Validator::required($message) || !$vendorIds) {
            Flash::error(__('line_message.select_and_write'));
            redirect('admin/line-messages');
        }

        $sent = 0;
        foreach (array_unique($vendorIds) as $vendorId) {
            $vendor = Vendor::find($vendorId);
            if ($vendor && $vendor['line_user_id'] && LineService::push($vendor['line_user_id'], $message)) {
                $sent++;
            }
        }

        $failed = count(array_unique($vendorIds)) - $sent;
        if ($sent > 0 && $failed === 0) {
            Flash::success(__('line_message.sent_success', ['count' => $sent]));
        } elseif ($sent > 0) {
            Flash::error(__('line_message.sent_partial', ['sent' => $sent, 'failed' => $failed]));
        } else {
            Flash::error(__('line_message.sent_failed'));
        }

        redirect('admin/line-messages');
    }
}
