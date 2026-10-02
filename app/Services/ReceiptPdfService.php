<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Renders the same booking receipt as resources/views/public/booking/receipt.php, but as a
 * real downloadable PDF — via tFPDF (vendor/tFPDF, Unicode/TrueType support, needed for Thai
 * text) and phpqrcode (vendor/phpqrcode) for the QR code, since the HTML receipt's QR is
 * drawn client-side in JS and has nothing to render server-side from. Both are vendored
 * directly (no Composer anywhere in this project) — see README.
 */
class ReceiptPdfService
{
    private const PAGE_MARGIN = 18;

    /**
     * Both vendored libraries (vendor/tFPDF, vendor/phpqrcode) predate PHP 8 and throw
     * harmless E_DEPRECATED notices from their own internals (param order, dynamic
     * properties) — suppressed for this whole call rather than relying on whatever the
     * site's error_reporting happens to be set to, since on a request where APP_DEBUG is
     * on, display_errors would otherwise inject that warning text into the middle of the
     * binary PDF output and corrupt the download.
     */
    public static function generate(array $booking, array $settings): string
    {
        $previousLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

        try {
            self::loadVendorLibraries();

            $pdf = new \tFPDF('P', 'mm', 'A4');
            $pdf->SetMargins(self::PAGE_MARGIN, self::PAGE_MARGIN, self::PAGE_MARGIN);
            $pdf->SetAutoPageBreak(true, self::PAGE_MARGIN);
            $pdf->AddFont('NotoSansThai', '', 'NotoSansThai-Regular.ttf', true);
            $pdf->AddFont('NotoSansThai', 'B', 'NotoSansThai-Bold.ttf', true);
            $pdf->AddPage();

            self::renderOrgHeader($pdf, $settings);
            self::renderTitle($pdf);
            self::renderCodeBox($pdf, $booking);

            if (in_array($booking['status'], ['pending_payment', 'booked'], true)) {
                self::renderQrCode($pdf, $booking['booking_code']);
            }

            self::renderSectionLabel($pdf, __('public.receipt_details'));
            self::renderInfoRow($pdf, __('event.singular'), self::eventName($booking));
            self::renderInfoRow($pdf, __('lot.singular'), $booking['lot_code']);
            self::renderInfoRow($pdf, __('lot.price'), money((float) $booking['price_at_booking'], $booking['currency_code']));
            self::renderInfoRow($pdf, __('booking.payment_method'), payment_method_label($booking['payment_method']));
            self::renderInfoRow($pdf, __('public.receipt_status'), booking_status_label($booking['status']));
            self::renderInfoRow($pdf, __('public.receipt_created_at'), date('d/m/Y H:i', strtotime($booking['created_at'])));

            self::renderSectionLabel($pdf, __('public.receipt_booker'));
            self::renderInfoRow($pdf, __('booking.booker_name'), $booking['booker_name']);
            self::renderInfoRow($pdf, __('booking.booker_phone'), $booking['booker_phone']);
            if (!empty($booking['booker_email'])) {
                self::renderInfoRow($pdf, __('booking.booker_email'), $booking['booker_email']);
            }

            if ($booking['payment_method'] === 'bank_transfer' && $booking['status'] === 'pending_payment') {
                self::renderSectionLabel($pdf, __('public.confirmation_instructions_bank'));
                self::renderInfoRow($pdf, __('settings.bank_name'), (string) ($settings['bank_name'] ?? ''));
                self::renderInfoRow($pdf, __('settings.bank_account_name'), (string) ($settings['bank_account_name'] ?? ''));
                self::renderInfoRow($pdf, __('settings.bank_account_number'), (string) ($settings['bank_account_number'] ?? ''));
                if (!empty($settings['promptpay_id'])) {
                    self::renderInfoRow($pdf, __('settings.promptpay_id'), $settings['promptpay_id']);
                }
            }

            $pdf->Ln(6);
            $pdf->SetFont('NotoSansThai', '', 9);
            $pdf->SetTextColor(140, 130, 110);
            $pdf->SetX(self::PAGE_MARGIN);
            self::renderWrappedText($pdf, 210 - 2 * self::PAGE_MARGIN, 5, __('public.receipt_generated_note', ['datetime' => date('d/m/Y H:i')]));

            return $pdf->Output('S');
        } finally {
            error_reporting($previousLevel);
        }
    }

    private static function loadVendorLibraries(): void
    {
        require_once BASE_PATH . '/vendor/tFPDF/tfpdf.php';
        require_once BASE_PATH . '/vendor/tFPDF/font/unifont/ttfonts.php';
        require_once BASE_PATH . '/vendor/phpqrcode/qrlib.php';
    }

    private static function eventName(array $booking): string
    {
        $locale = \App\Core\Lang::locale();
        if ($locale === 'en' && !empty($booking['event_name_en'])) {
            return $booking['event_name_en'];
        }
        return $booking['event_name_th'];
    }

    private static function renderOrgHeader(\tFPDF $pdf, array $settings): void
    {
        $orgName = $settings['org_name'] ?: __('common.app_name');

        $pdf->SetFont('NotoSansThai', 'B', 15);
        $pdf->SetTextColor(36, 28, 16);
        $pdf->Cell(0, 7, $orgName, 0, 1);

        $meta = trim(($settings['org_address'] ?? '') . (
            !empty($settings['org_address']) && !empty($settings['org_phone']) ? ' · ' : ''
        ) . ($settings['org_phone'] ?? ''));
        if ($meta !== '') {
            $pdf->SetFont('NotoSansThai', '', 9);
            $pdf->SetTextColor(122, 106, 84);
            $pdf->SetX(self::PAGE_MARGIN);
            self::renderWrappedText($pdf, 210 - 2 * self::PAGE_MARGIN, 5, $meta);
        }

        $pdf->SetDrawColor(234, 217, 186);
        $pdf->SetLineWidth(0.3);
        $pdf->Ln(2);
        $pdf->Line(self::PAGE_MARGIN, $pdf->GetY(), 210 - self::PAGE_MARGIN, $pdf->GetY());
        $pdf->Ln(5);
    }

    private static function renderTitle(\tFPDF $pdf): void
    {
        $pdf->SetFont('NotoSansThai', 'B', 18);
        $pdf->SetTextColor(36, 28, 16);
        $pdf->Cell(0, 10, __('public.receipt_title'), 0, 1, 'C');
        $pdf->Ln(2);
    }

    private static function renderCodeBox(\tFPDF $pdf, array $booking): void
    {
        $boxHeight = 20;
        $y = $pdf->GetY();
        $pdf->SetFillColor(252, 234, 203);
        $pdf->Rect(self::PAGE_MARGIN, $y, 210 - 2 * self::PAGE_MARGIN, $boxHeight, 'F');

        $pdf->SetFont('NotoSansThai', '', 9);
        $pdf->SetTextColor(162, 84, 10);
        $pdf->SetXY(self::PAGE_MARGIN, $y + 3);
        $pdf->Cell(0, 5, __('public.confirmation_code_label'), 0, 1, 'C');

        $pdf->SetFont('NotoSansThai', 'B', 16);
        $pdf->SetTextColor(194, 101, 12);
        $pdf->SetXY(self::PAGE_MARGIN, $y + 9);
        $pdf->Cell(0, 8, $booking['booking_code'], 0, 1, 'C');

        $pdf->SetY($y + $boxHeight + 6);
    }

    private static function renderQrCode(\tFPDF $pdf, string $bookingCode): void
    {
        // storage/tmp/, not sys_get_temp_dir() — on this host (and plausibly on shared
        // hosting too) the webserver process doesn't have permission to the system temp
        // directory PHP's CLI SAPI resolves to, which makes tempnam() silently return false
        // and the image path collapse to the literal string ".png". storage/ is already
        // proven writable by whatever user runs PHP, since BackupService writes there.
        $tmpDir = BASE_PATH . '/storage/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        // uniqid(), not tempnam() — tempnam() creates its reserved file under the bare name
        // it returns, but QRcode::png() needs a .png-suffixed path to pick its output format,
        // so appending ".png" afterward would leave that original reserved file behind unused
        // on every single call (see BackupService::create() for the same uniqid() pattern).
        $tmpPng = $tmpDir . '/' . uniqid('receiptqr_', true) . '.png';
        \QRcode::png($bookingCode, $tmpPng, QR_ECLEVEL_M, 6, 2);

        $size = 32;
        $x = (210 - $size) / 2;
        $pdf->Image($tmpPng, $x, $pdf->GetY(), $size, $size, 'PNG');
        $pdf->SetY($pdf->GetY() + $size + 4);

        unlink($tmpPng);
    }

    private static function renderSectionLabel(\tFPDF $pdf, string $label): void
    {
        $pdf->Ln(3);
        $pdf->SetFont('NotoSansThai', 'B', 10);
        $pdf->SetTextColor(194, 101, 12);
        $pdf->Cell(0, 6, $label, 0, 1);
        $pdf->SetDrawColor(243, 231, 206);
        $pdf->Line(self::PAGE_MARGIN, $pdf->GetY(), 210 - self::PAGE_MARGIN, $pdf->GetY());
        $pdf->Ln(2);
    }

    private static function renderInfoRow(\tFPDF $pdf, string $label, string $value): void
    {
        $labelWidth = 48;
        $valueWidth = 210 - 2 * self::PAGE_MARGIN - $labelWidth;

        $pdf->SetFont('NotoSansThai', '', 10);
        $pdf->SetTextColor(122, 106, 84);
        $y = $pdf->GetY();
        $pdf->SetXY(self::PAGE_MARGIN, $y);
        $pdf->Cell($labelWidth, 6, $label);

        $pdf->SetTextColor(36, 28, 16);
        $pdf->SetXY(self::PAGE_MARGIN + $labelWidth, $y);
        self::renderWrappedText($pdf, $valueWidth, 6, $value);
    }

    /**
     * Thai nonspacing combining marks (tone marks, sara vowels that stack on the base
     * consonant) — U+0E31, U+0E34–U+0E3A, U+0E47–U+0E4E. These are legitimately zero-width
     * (they render stacked on the previous character, not side-by-side), but tFPDF's
     * GetStringWidth() returns garbage for them specifically (~230mm instead of ~0 — verified
     * by direct measurement, looks like a bug in the bundled TTF metrics table for this font's
     * zero-advance-width glyphs). Nearly every Thai word has at least one of these, so this
     * isn't an edge case — measureWidth() below skips them rather than trusting that value.
     */
    private const THAI_COMBINING_MARKS = [
        0x0E31, 0x0E34, 0x0E35, 0x0E36, 0x0E37, 0x0E38, 0x0E39, 0x0E3A,
        0x0E47, 0x0E48, 0x0E49, 0x0E4A, 0x0E4B, 0x0E4C, 0x0E4D, 0x0E4E,
    ];

    private static function measureWidth(\tFPDF $pdf, string $text): float
    {
        $width = 0.0;
        foreach (mb_str_split($text, 1, 'UTF-8') as $char) {
            if (in_array(mb_ord($char, 'UTF-8'), self::THAI_COMBINING_MARKS, true)) {
                continue;
            }
            $width += $pdf->GetStringWidth($char);
        }
        return $width;
    }

    /**
     * tFPDF's own MultiCell() finds line breaks by searching for ASCII space characters —
     * Thai script doesn't put spaces between words at all, so a run of Thai text wider than
     * the cell makes it fall back to breaking almost every character onto its own line. This
     * wraps manually instead: greedy word-wrap on spaces when they exist (handles Latin text
     * and mixed Thai/English fine), falling back to a character-by-character break only for
     * a single unbroken run that's wider than the whole available width on its own.
     * @return string[]
     */
    private static function wrapLines(\tFPDF $pdf, string $text, float $maxWidth): array
    {
        $lines = [];
        $words = preg_split('/( )/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current . $word;
            if (self::measureWidth($pdf, $candidate) <= $maxWidth) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = rtrim($current);
                $current = '';
            }

            if (self::measureWidth($pdf, $word) <= $maxWidth) {
                $current = $word;
                continue;
            }

            $chunk = '';
            foreach (mb_str_split($word, 1, 'UTF-8') as $char) {
                if ($chunk !== '' && self::measureWidth($pdf, $chunk . $char) > $maxWidth) {
                    $lines[] = $chunk;
                    $chunk = '';
                }
                $chunk .= $char;
            }
            $current = $chunk;
        }

        if ($current !== '') {
            $lines[] = rtrim($current);
        }

        return $lines ?: [''];
    }

    private static function renderWrappedText(\tFPDF $pdf, float $maxWidth, float $lineHeight, string $text): void
    {
        foreach (self::wrapLines($pdf, $text, $maxWidth) as $line) {
            $pdf->Cell($maxWidth, $lineHeight, $line, 0, 2);
        }
    }
}
