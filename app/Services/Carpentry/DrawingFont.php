<?php

namespace App\Services\Carpentry;

class DrawingFont
{
    private static ?string $bold = null;

    private static ?string $regular = null;

    private static bool $resolved = false;

    public static function path(bool $bold = false): ?string
    {
        self::resolve();

        if ($bold) {
            return self::$bold ?? self::$regular;
        }

        return self::$regular ?? self::$bold;
    }

    public static function textWidth(string $text, int $size, bool $bold = false): int
    {
        $font = self::path($bold);

        if ($font === null || ! function_exists('imagettfbbox')) {
            return strlen($text) * (int) round($size * 0.6);
        }

        $box = imagettfbbox($size, 0, $font, self::latin1($text));

        return abs($box[2] - $box[0]);
    }

    public static function draw($image, int $size, int $x, int $y, string $text, int $color, bool $bold = false): void
    {
        $font = self::path($bold);
        $text = self::latin1($text);

        if ($font !== null && function_exists('imagettftext')) {
            imagettftext($image, $size, 0, $x, $y, $color, $font, $text);

            return;
        }

        imagestring($image, $size >= 16 ? 5 : 3, $x, $y - $size, $text, $color);
    }

    public static function latin1(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        return is_string($converted) ? $converted : $text;
    }

    private static function resolve(): void
    {
        if (self::$resolved) {
            return;
        }

        self::$resolved = true;

        $boldCandidates = [
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/Library/Fonts/Arial Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            dirname(__DIR__, 3).'/vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf',
        ];

        $regularCandidates = [
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/Library/Fonts/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            dirname(__DIR__, 3).'/vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf',
        ];

        foreach ($boldCandidates as $candidate) {
            if (is_readable($candidate)) {
                self::$bold = $candidate;
                break;
            }
        }

        foreach ($regularCandidates as $candidate) {
            if (is_readable($candidate)) {
                self::$regular = $candidate;
                break;
            }
        }
    }
}
