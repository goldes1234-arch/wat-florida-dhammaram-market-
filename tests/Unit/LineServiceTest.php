<?php

namespace Tests\Unit;

use App\Services\LineService;
use Tests\TestCase;

/** Covers the /line/webhook signature check — the one thing standing between that endpoint and a forged request. */
class LineServiceTest extends TestCase
{
    public function testVerifySignatureAcceptsCorrectHmac(): void
    {
        $secret = 'test-secret';
        $body = '{"events":[]}';
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->assertTrue(LineService::verifySignature($body, $signature, $secret));
    }

    public function testVerifySignatureRejectsTamperedBody(): void
    {
        $secret = 'test-secret';
        $body = '{"events":[]}';
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->assertFalse(LineService::verifySignature($body . 'tampered', $signature, $secret));
    }

    public function testVerifySignatureRejectsWrongSecret(): void
    {
        $body = '{"events":[]}';
        $signature = base64_encode(hash_hmac('sha256', $body, 'right-secret', true));

        $this->assertFalse(LineService::verifySignature($body, $signature, 'wrong-secret'));
    }

    public function testVerifySignatureRejectsEmptySignatureOrSecret(): void
    {
        $this->assertFalse(LineService::verifySignature('body', '', 'secret'));
        $this->assertFalse(LineService::verifySignature('body', 'some-signature', ''));
    }
}
