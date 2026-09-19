<?php

namespace App\Controllers\Admin;

use App\Core\ActivityLog;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Models\AdminUser;
use App\Support\Validator;

class StaffController
{
    public function index(Request $request): void
    {
        View::render('admin/staff/index', [
            'title' => __('nav.staff'),
            'active' => 'staff',
            'users' => AdminUser::all(),
        ], 'admin');
    }

    public function store(Request $request): void
    {
        $name = $request->trimmed('name');
        $email = $request->trimmed('email');
        $password = (string) $request->input('password', '');
        $role = $request->trimmed('role');

        $errors = [];
        if (!Validator::required($name)) {
            $errors[] = __('validation.required', ['field' => __('staff.name')]);
        }
        if (!Validator::required($email) || !Validator::email($email)) {
            $errors[] = __('validation.invalid_email');
        } elseif (AdminUser::emailExists($email)) {
            $errors[] = __('staff.email_taken');
        }
        if (strlen($password) < 8) {
            $errors[] = __('staff.password_too_short');
        }
        if (!in_array($role, ['super_admin', 'staff', 'checkin'], true)) {
            $errors[] = __('validation.generic_error');
        }

        if ($errors) {
            Flash::error(implode(' ', $errors));
            redirect('admin/staff');
        }

        $newId = AdminUser::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        ActivityLog::record('staff.create', 'admin_user', $newId, __('activity.staff_created', ['name' => $name, 'role' => $role]));

        Flash::success(__('staff.created_success'));
        redirect('admin/staff');
    }

    public function toggleActive(Request $request, string $id): void
    {
        $user = AdminUser::find((int) $id);
        if (!$user) {
            redirect('admin/staff');
        }

        if ((int) $user['id'] === (int) (Auth::user()['id'] ?? 0)) {
            Flash::error(__('staff.cannot_deactivate_self'));
            redirect('admin/staff');
        }

        $activating = !$user['is_active'];
        AdminUser::setActive((int) $id, $activating);

        ActivityLog::record(
            $activating ? 'staff.activate' : 'staff.deactivate',
            'admin_user',
            (int) $id,
            __($activating ? 'activity.staff_activated' : 'activity.staff_deactivated', ['name' => $user['name']])
        );

        Flash::success(__('staff.updated_success'));
        redirect('admin/staff');
    }
}
