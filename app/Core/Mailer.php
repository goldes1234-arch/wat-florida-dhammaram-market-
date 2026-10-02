<?php

namespace App\Core;

use App\Models\Setting;
use App\Services\SmtpMailer;

class Mailer
{
    /**
     * Sends an HTML email — via real SMTP once one is configured in Settings,
     * otherwise falls back to PHP's bare mail(). Regardless of which path is
     * used (or whether it succeeds), the rendered email is always written to
     * storage/logs/emails/ so the notification flow stays fully verifiable
     * during testing.
     */
    public static function send(string $to, string $subject, string $html): bool
    {
        self::logToFile($to, $subject, $html);

        // Staging never delivers a real email — whatever's in Settings there (even if someone
        // copied production's real SMTP creds in by mistake) stays local to storage/logs/emails/,
        // so testing a booking flow on staging can't spam a real vendor's inbox.
        if (App::config('app.env') === 'staging') {
            return true;
        }

        $settings = Setting::get();
        $config = App::config('mail');
        $fromName = $settings['smtp_from_name'] ?? '' ?: $config['from_name'];
        $fromEmail = $settings['smtp_from_email'] ?? '' ?: $config['from_address'];

        if (!empty($settings['smtp_host'])) {
            [$sent, $error] = SmtpMailer::send($settings, $to, $fromEmail, $fromName, $subject, $html);
            if (!$sent) {
                error_log('SMTP send failed to ' . $to . ': ' . $error);
            }
            return $sent;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
        ];

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        set_error_handler(static function () {
            return true;
        });
        $sent = @mail($to, $encodedSubject, $html, implode("\r\n", $headers));
        restore_error_handler();

        return (bool) $sent;
    }

    private static function logToFile(string $to, string $subject, string $html): void
    {
        $dir = BASE_PATH . '/storage/logs/emails';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9_.@-]/', '_', $to) . '.html';
        $body = "<!-- To: {$to} -->\n<!-- Subject: " . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . " -->\n" . $html;

        file_put_contents($dir . '/' . $filename, $body);
    }
}
