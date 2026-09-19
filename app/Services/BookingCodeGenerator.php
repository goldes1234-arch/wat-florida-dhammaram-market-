<?php

namespace App\Services;

use App\Models\Booking;

class BookingCodeGenerator
{
    // Unambiguous alphabet: no 0/O or 1/I.
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = 'TM-' . self::randomChunk(6);
            if (!Booking::codeExists($code)) {
                return $code;
            }
        }

        // Astronomically unlikely fallback: widen with a timestamp suffix.
        return 'TM-' . self::randomChunk(6) . dechex(time());
    }

    private static function randomChunk(int $length): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $chunk = '';
        for ($i = 0; $i < $length; $i++) {
            $chunk .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }
        return $chunk;
    }
}
