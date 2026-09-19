<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;

class AuthController
{
    public function loginForm(Request $request): void
    {
        View::render('admin/auth/login', ['title' => __('auth.login_title')], 'auth');
    }

    public function login(Request $request): void
    {
        $email = $request->trimmed('email');
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            Flash::error(__('auth.invalid_credentials'));
            Flash::setOld(['email' => $email]);
            redirect('admin/login');
        }

        redirect('admin');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Flash::success(__('auth.logged_out'));
        redirect('admin/login');
    }
}
