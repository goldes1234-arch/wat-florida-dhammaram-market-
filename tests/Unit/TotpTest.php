<?php

namespace Tests\Unit;

use App\Core\Totp;
use Tests\TestCase;

/**
 * Verifies the hand-rolled TOTP implementation against RFC 6238's own SHA1 test
 * vectors (Appendix B) — those specify 8-digit codes for the raw ASCII key
 * "12345678901234567890"; this project uses 6 digits (matching what real
 * authenticator apps show), and the last 6 digits of an RFC vector are
 * mathematically identical to the 6-digit truncation (10^8 is a multiple of
 * 10^6), so comparing against the last 6 digits is a faithful check.
 */
class TotpTest extends TestCase
{
    private function rfcTestSecret(): string
    {
        $ref = new \ReflectionClass(Totp::class);
        $encode = $ref->getMethod('base32Encode');
        $encode->setAccessible(true);
        return $encode->invoke(null, '12345678901234567890');
    }

    public function testMatchesRfc6238OfficialTestVectors(): void
    {
        $secret = $this->rfcTestSecret();

        $vectors = [
            59 => '94287082',
            1111111109 => '07081804',
            1111111111 => '14050471',
            1234567890 => '89005924',
            2000000000 => '69279037',
        ];

        foreach ($vectors as $time => $expected8Digit) {
            $timeStep = (int) floor($time / 30);
            $expected6Digit = substr($expected8Digit, -6);
            $this->assertSame($expected6Digit, Totp::codeAt($secret, $timeStep), "mismatch at unix time $time");
        }
    }

    public function testVerifyAcceptsTheCurrentCodeAndRejectsAWrongOne(): void
    {
        $secret = Totp::generateSecret();
        $validCode = Totp::codeAt($secret, Totp::currentTimeStep());

        $this->assertTrue(Totp::verify($secret, $validCode));
        $this->assertFalse(Totp::verify($secret, '000000'));
    }

    public function testVerifyToleratesOneStepOfClockDrift(): void
    {
        $secret = Totp::generateSecret();
        $previousStepCode = Totp::codeAt($secret, Totp::currentTimeStep() - 1);

        $this->assertTrue(Totp::verify($secret, $previousStepCode, 1), 'a code from one step ago should still be accepted within the drift window');
        $this->assertFalse(Totp::verify($secret, $previousStepCode, 0), 'with no drift window, an old code must not be accepted');
    }

    public function testVerifyRejectsMalformedInput(): void
    {
        $secret = Totp::generateSecret();

        $this->assertFalse(Totp::verify($secret, ''));
        $this->assertFalse(Totp::verify($secret, '12345')); // too short
        $this->assertFalse(Totp::verify($secret, 'abcdef')); // not digits
    }
}
