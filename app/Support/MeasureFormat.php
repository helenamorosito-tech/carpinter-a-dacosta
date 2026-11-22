<?php

namespace App\Support;

class MeasureFormat
{
    /**
     * Formato de medidas sin ceros decimales innecesarios.
     * Ej.: 60 → "60", 193.5 → "193,5", 122.25 → "122,25".
     */
    public static function number(
        float|int|string|null $value,
        int $maxDecimals = 2,
        string $decimalSeparator = ',',
    ): string {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = round((float) $value, $maxDecimals);

        if (abs($number - round($number)) < 0.0000001) {
            return (string) (int) round($number);
        }

        $formatted = number_format($number, $maxDecimals, $decimalSeparator, '');

        return rtrim(rtrim($formatted, '0'), $decimalSeparator);
    }

    /**
     * Variante con punto decimal (diagramas PNG/SVG).
     */
    public static function numberDot(float|int|string|null $value, int $maxDecimals = 2): string
    {
        return self::number($value, $maxDecimals, '.');
    }

    public static function pair(
        float|int|string|null $width,
        float|int|string|null $height,
        string $separator = '×',
        string $decimalSeparator = ',',
    ): string {
        return self::number($width, 2, $decimalSeparator)
            .$separator
            .self::number($height, 2, $decimalSeparator);
    }

    public static function pairDot(
        float|int|string|null $width,
        float|int|string|null $height,
        string $separator = '×',
    ): string {
        return self::pair($width, $height, $separator, '.');
    }
}
