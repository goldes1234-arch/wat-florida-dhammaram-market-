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

            // Greet with the sender's own domain — "EHLO localhost" is a common
            // spam-filter signal and ends up in the Received header.
            $senderDomain = self::domainOf($fromEmail) ?: $host;
            $localhost = $senderDomain;

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

            $message = self::buildMessage($to, $fromEmail, $fromName, $subject, $html, $senderDomain) . "\r\n.";

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

    /**
     * multipart/alternative (plain text + HTML) — HTML-only mail scores worse
     * with spam filters. Both parts are base64 so no line exceeds SMTP's 998
     * char limit and no line can start with "." (no dot-stuffing needed).
     */
    private static function buildMessage(string $to, string $fromEmail, string $fromName, string $subject, string $html, string $senderDomain): string
    {
        $boundary = 'b_' . bin2hex(random_bytes(12));

        $headers = [
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $senderDomain . '>',
            'From: ' . self::encodeName($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: <' . $fromEmail . '>',
            'To: <' . $to . '>',
            'Subject: ' . self::encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $parts = [
            ['text/plain', self::htmlToText($html)],
            ['text/html', $html],
        ];

        $body = '';
        foreach ($parts as [$type, $content]) {
            $body .= '--' . $boundary . "\r\n"
                . 'Content-Type: ' . $type . "; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . rtrim(chunk_split(base64_encode($content), 76, "\r\n")) . "\r\n";
        }
        $body .= '--' . $boundary . '--';

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private static function htmlToText(string $html): string
    {
        $text = preg_replace('#<(br|/p|/div|/h[1-6]|/li|/tr)\b[^>]*>#i', "\n", $html);
        $text = preg_replace('#<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $text);
        $text = preg_replace('#<(style|script)\b.*?</\1>#is', '', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text);

        return trim($text);
    }

    /** Display name for From: — RFC 2047-encoded when it has non-ASCII (e.g. Thai), quoted otherwise. */
    private static function encodeName(string $name): string
    {
        $name = trim(str_replace(["\r", "\n"], '', $name));
        if (preg_match('/[^\x20-\x7E]/', $name)) {
            return self::encodeHeader($name);
        }

        return '"' . addcslashes($name, '"\\') . '"';
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode(str_replace(["\r", "\n"], '', $value)) . '?=';
    }

    private static function domainOf(string $email): string
    {
        $at = strrpos($email, '@');

        return $at === false ? '' : strtolower(substr($email, $at + 1));
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
