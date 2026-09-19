<?php

namespace App\Services;

/**
 * Minimal hand-rolled SMTP client (no PHPMailer/Composer) — plain socket
 * conversation supporting implicit SSL (port 465) or STARTTLS (port 587/25),
 * AUTH LOGIN, and a single HTML message per call. Good enough for the
 * low-volume mail a single temple's booking system sends.
 */
class SmtpMailer
{
    /** @return array{0: bool, 1: string} [success, error message] */
    public static function send(array $config, string $to, string $fromEmail, string $fromName, string $subject, string $html): array
    {
        $host = $config['smtp_host'];
        $port = (int) ($config['smtp_port'] ?: 587);
        $encryption = $config['smtp_encryption'] ?: 'tls';
        $username = $config['smtp_username'] ?? '';
        $password = $config['smtp_password'] ?? '';

        $target = $encryption === 'ssl' ? 'ssl://' . $host : $host;

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($target, $port, $errno, $errstr, 15);
        if (!$socket) {
            return [false, "Connection failed: {$errstr} ({$errno})"];
        }
        stream_set_timeout($socket, 15);

        try {
            [$ok, $error] = self::expect($socket, '220');
            if (!$ok) {
                return [false, "Greeting failed: {$error}"];
            }

            $localhost = 'localhost';

            self::command($socket, 'EHLO ' . $localhost);
            [$ok, $error] = self::expect($socket, '250');
            if (!$ok) {
                return [false, "EHLO failed: {$error}"];
            }

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS');
                [$ok, $error] = self::expect($socket, '220');
                if (!$ok) {
                    return [false, "STARTTLS failed: {$error}"];
                }
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return [false, 'TLS negotiation failed'];
                }
                self::command($socket, 'EHLO ' . $localhost);
                [$ok, $error] = self::expect($socket, '250');
                if (!$ok) {
                    return [false, "EHLO after STARTTLS failed: {$error}"];
                }
            }

            if ($username !== '') {
                self::command($socket, 'AUTH LOGIN');
                [$ok, $error] = self::expect($socket, '334');
                if (!$ok) {
                    return [false, "AUTH LOGIN failed: {$error}"];
                }
                self::command($socket, base64_encode($username));
                [$ok, $error] = self::expect($socket, '334');
                if (!$ok) {
                    return [false, "AUTH username rejected: {$error}"];
                }
                self::command($socket, base64_encode($password));
                [$ok, $error] = self::expect($socket, '235');
                if (!$ok) {
                    return [false, "AUTH password rejected: {$error}"];
                }
            }

            self::command($socket, 'MAIL FROM:<' . $fromEmail . '>');
            [$ok, $error] = self::expect($socket, '250');
            if (!$ok) {
                return [false, "MAIL FROM rejected: {$error}"];
            }

            self::command($socket, 'RCPT TO:<' . $to . '>');
            [$ok, $error] = self::expect($socket, ['250', '251']);
            if (!$ok) {
                return [false, "RCPT TO rejected: {$error}"];
            }

            self::command($socket, 'DATA');
            [$ok, $error] = self::expect($socket, '354');
            if (!$ok) {
                return [false, "DATA rejected: {$error}"];
            }

            $headers = [
                'Date: ' . date('r'),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $host . '>',
                'From: ' . $fromName . ' <' . $fromEmail . '>',
                'To: <' . $to . '>',
                'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $body = str_replace("\r\n.\r\n", "\r\n..\r\n", $html);
            $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";

            self::command($socket, $message);
            [$ok, $error] = self::expect($socket, '250');
            if (!$ok) {
                return [false, "Message rejected: {$error}"];
            }

            self::command($socket, 'QUIT');

            return [true, ''];
        } finally {
            fclose($socket);
        }
    }

    private static function command($socket, string $line): void
    {
        fwrite($socket, $line . "\r\n");
    }

    /** @param string|string[] $expectedCodes @return array{0: bool, 1: string} */
    private static function expect($socket, $expectedCodes): array
    {
        $codes = (array) $expectedCodes;
        $lastLine = '';

        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $lastLine = $line;
            // Multi-line responses use "250-text"; the final line uses "250 text".
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = substr($lastLine, 0, 3);
        return [in_array($code, $codes, true), trim($lastLine)];
    }
}
