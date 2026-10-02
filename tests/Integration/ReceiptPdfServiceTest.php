<?php

namespace Tests\Integration;

use App\Services\ReceiptPdfService;
use Tests\TestCase;

/**
 * Builds PDFs from in-memory fixtures (no booking rows needed) and inspects the raw output.
 */
class ReceiptPdfServiceTest extends TestCase
{
    private function booking(array $overrides = []): array
    {
        return $overrides + [
            'booking_code' => 'TM-TEST01',
            'status' => 'pending_payment',
            'event_name_th' => 'งานทดสอบ',
            'event_name_en' => 'Test event',
            'lot_code' => 'A01',
            'price_at_booking' => 20,
            'currency_code' => 'THB',
            'payment_method' => 'onsite_cash',
            'created_at' => '2026-09-19 14:29:00',
            'booker_name' => 'สมชาย ใจดี',
            'booker_phone' => '0812345678',
            'booker_email' => null,
        ];
    }

    private function settings(array $overrides = []): array
    {
        return $overrides + [
            'org_name' => 'วัดทดสอบ',
            'org_address' => '1 Test Rd',
            'org_phone' => '000-000-0000',
            'logo_path' => null,
            'bank_name' => '',
            'bank_account_name' => '',
            'bank_account_number' => '',
            'promptpay_id' => '',
        ];
    }

    private function pageCount(string $pdf): int
    {
        return (int) preg_match_all('#/Type /Page(?![a-z])#', $pdf);
    }

    private function tempFileCount(): int
    {
        return count(glob(BASE_PATH . '/storage/tmp/receipt*') ?: []);
    }

    public function testProducesAValidSinglePagePdf(): void
    {
        $pdf = ReceiptPdfService::generate($this->booking(), $this->settings());

        $this->assertTrue(str_starts_with($pdf, '%PDF-'), 'output should start with a PDF header');
        $this->assertTrue(str_contains(substr($pdf, -16), '%%EOF'), 'output should end with a PDF trailer');
        $this->assertSame(1, $this->pageCount($pdf));
    }

    /**
     * tFPDF's GetStringWidth() returns ~230mm for Thai combining marks (tone marks, stacking
     * vowels), which made a long unbroken Thai string wrap one character per line and spill
     * across many pages. ReceiptPdfService does its own width measuring to avoid that.
     */
    public function testLongUnbrokenThaiTextStillFitsOnOnePage(): void
    {
        $longName = str_repeat('สวัสดีครับผมชื่อสมชายใจดี', 8);

        $pdf = ReceiptPdfService::generate($this->booking(['booker_name' => $longName]), $this->settings());

        $this->assertSame(1, $this->pageCount($pdf));
    }

    public function testLeavesNoTemporaryFilesBehind(): void
    {
        $before = $this->tempFileCount();

        ReceiptPdfService::generate($this->booking(), $this->settings());

        $this->assertSame($before, $this->tempFileCount());
    }

    public function testALogoPathEscapingTheUploadsFolderIsIgnoredNotEmbedded(): void
    {
        $pdf = ReceiptPdfService::generate(
            $this->booking(),
            $this->settings(['logo_path' => '../.env'])
        );

        $this->assertSame(1, $this->pageCount($pdf));
        $this->assertFalse(str_contains($pdf, 'APP_ENV'), 'a path outside uploads/ must never be read into the PDF');
    }
}
