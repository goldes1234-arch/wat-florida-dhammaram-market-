<?php

namespace Tests\Unit;

use App\Controllers\Public\LineWebhookController as Hook;
use Tests\TestCase;

/** The bot must answer commands and bare phone numbers only, so admins can chat by hand in the OA without it interrupting. */
class LineWebhookClassifyTest extends TestCase
{
    public function testAnUnlinkedAccountSendingJustAPhoneNumberStartsALinkRequest(): void
    {
        foreach (['0812345678', '081-234-5678', '081 234 5678', '+1 (407) 951-0424', '4079510424'] as $text) {
            $this->assertSame(Hook::ACTION_LINK, Hook::classify($text, false), $text);
        }
    }

    public function testAnUnlinkedAccountChattingNormallyIsLeftAlone(): void
    {
        foreach (['สวัสดีครับ', 'Hi, I want to sell food at the festival', 'โทร 0812345678 ได้ไหมครับ', '12345', 'สถานะ', 'ยืนยัน'] as $text) {
            $this->assertSame(Hook::ACTION_IGNORE, Hook::classify($text, false), $text);
        }
    }

    public function testALinkedVendorGetsRepliesOnlyForTheTwoCommands(): void
    {
        $this->assertSame(Hook::ACTION_CONFIRM, Hook::classify('ยืนยัน', true));
        $this->assertSame(Hook::ACTION_CONFIRM, Hook::classify(' Confirm ', true));
        $this->assertSame(Hook::ACTION_PORTAL, Hook::classify('สถานะ', true));
        $this->assertSame(Hook::ACTION_PORTAL, Hook::classify('History', true));
    }

    public function testALinkedVendorSendingAnythingElseIsLeftForAHuman(): void
    {
        foreach (['ขอบคุณครับ', 'ขายอะไรได้บ้าง', '0812345678', 'ยืนยันแล้วนะครับ'] as $text) {
            $this->assertSame(Hook::ACTION_IGNORE, Hook::classify($text, true), $text);
        }
    }
}
