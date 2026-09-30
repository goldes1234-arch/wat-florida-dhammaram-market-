<?php

namespace App\Core;

/**
 * Hand-rolled RFC 6238 TOTP (the same algorithm Google Authenticator, Authy,
 * 1Password, etc. all implement) — no external dependency, matching how the
 * rest of this project avoids Composer packages. See AdminUser 2FA fields and
 * AuthController's login-step-2 flow for how this is actually used.
 */
class Totp
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function provisioningUri(string $secret, string $accountLabel, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $accountLabel)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    /** Accepts a code from up to $window time-steps before/after now, to tolerate clock drift. */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', (string) $code)) {
            return false;
        }

        $currentStep = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $currentStep + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /** Exposed for tests — the code a real authenticator app would show for $secret at $timeStep. */
    public static function codeAt(string $secret, int $timeStep): string
    {
        $key = self::base32Decode($secret);
        $counter = pack('N*', 0, $timeStep); // 8-byte big-endian counter (32-bit step count fits in the low word)
        $hash = hash_hmac('sha1', $counter, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code = $binary % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function currentTimeStep(): int
    {
        return (int) floor(time() / self::PERIOD);
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }
        return $output;
    }

    private static function base32Decode(string $data): string
    {
        $data = strtoupper((string) preg_replace('/[^A-Z2-7]/i', '', $data));
        $bits = '';
        foreach (str_split($data) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        return $bytes;
    }
}
