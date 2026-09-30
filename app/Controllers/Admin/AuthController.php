<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Totp;
use App\Core\View;
use App\Models\AdminUser;
use App\Models\Setting;

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

        // Recorded on every attempt (not just failures), same as sendResetLink() below —
        // a genuine admin essentially never needs 15 login tries in an hour, so this only
        // ever bites a brute-force script, not a forgetful person.
        if (RateLimiter::tooMany($request->ip(), 'admin_login', 15)) {
            Flash::error(__('auth.login_rate_limited'));
            Flash::setOld(['email' => $email]);
            redirect('admin/login');
        }

        $result = ($email === '' || $password === '') ? 'invalid' : Auth::attempt($email, $password);

        if ($result === 'invalid') {
            Flash::error(__('auth.invalid_credentials'));
            Flash::setOld(['email' => $email]);
            redirect('admin/login');
        }

        if ($result === 'needs_2fa') {
            redirect('admin/login/verify-2fa');
        }

        redirect('admin');
    }

    public function verify2faForm(Request $request): void
    {
        if (!Auth::pendingTwoFactorUser()) {
            redirect('admin/login');
        }

        View::render('admin/auth/verify_2fa', ['title' => __('auth.verify_2fa_title')], 'auth');
    }

    public function verify2fa(Request $request): void
    {
        $user = Auth::pendingTwoFactorUser();
        if (!$user) {
            Flash::error(__('auth.login_session_expired'));
            redirect('admin/login');
        }

        if (RateLimiter::tooMany($request->ip(), 'admin_login_2fa', 15)) {
            Flash::error(__('auth.login_rate_limited'));
            redirect('admin/login/verify-2fa');
        }

        $code = $request->trimmed('code');
        $secret = (string) ($user['totp_secret'] ?? '');

        if ($secret && Totp::verify($secret, $code)) {
            Auth::completeTwoFactorLogin();
            redirect('admin');
        }

        // Not a valid live TOTP code — try it as a one-time backup code instead.
        $codeHash = hash('sha256', strtoupper(trim($code)));
        if (AdminUser::consumeBackupCode((int) $user['id'], $codeHash)) {
            Auth::completeTwoFactorLogin();
            Flash::success(__('auth.backup_code_used'));
            redirect('admin');
        }

        Flash::error(__('auth.invalid_2fa_code'));
        redirect('admin/login/verify-2fa');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Flash::success(__('auth.logged_out'));
        redirect('admin/login');
    }

    public function forgotPasswordForm(Request $request): void
    {
        View::render('admin/auth/forgot_password', ['title' => __('auth.forgot_password_title')], 'auth');
    }

    public function sendResetLink(Request $request): void
    {
        $email = $request->trimmed('email');

        if (RateLimiter::tooMany($request->ip(), 'password_reset', 5)) {
            Flash::error(__('auth.reset_rate_limited'));
            redirect('admin/forgot-password');
        }

        $user = $email !== '' ? AdminUser::findByEmail($email) : null;
        if ($user && $user['is_active']) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            AdminUser::setResetToken((int) $user['id'], $hash, date('Y-m-d H:i:s', time() + 3600));

            $link = full_url('admin/reset-password/' . $token);
            $html = View::renderToString('emails/password_reset', [
                'user' => $user,
                'link' => $link,
                'settings' => Setting::get(),
            ]);
            Mailer::send($user['email'], __('email.password_reset_subject'), $html);
        }

        // Same message whether or not the email exists, so this can't be used to enumerate admin accounts.
        Flash::success(__('auth.reset_link_sent'));
        redirect('admin/forgot-password');
    }

    public function resetPasswordForm(Request $request, string $token): void
    {
        $user = AdminUser::findByValidResetTokenHash(hash('sha256', $token));
        if (!$user) {
            Flash::error(__('auth.reset_token_invalid'));
            redirect('admin/forgot-password');
        }

        View::render('admin/auth/reset_password', [
            'title' => __('auth.reset_password_title'),
            'token' => $token,
        ], 'auth');
    }

    public function resetPassword(Request $request, string $token): void
    {
        $user = AdminUser::findByValidResetTokenHash(hash('sha256', $token));
        if (!$user) {
            Flash::error(__('auth.reset_token_invalid'));
            redirect('admin/forgot-password');
        }

        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        if (mb_strlen($password) < 8) {
            Flash::error(__('auth.reset_password_too_short'));
            redirect('admin/reset-password/' . $token);
        }
        if ($password !== $confirm) {
            Flash::error(__('auth.reset_password_mismatch'));
            redirect('admin/reset-password/' . $token);
        }

        AdminUser::resetPassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        Flash::success(__('auth.reset_password_success'));
        redirect('admin/login');
    }
}
