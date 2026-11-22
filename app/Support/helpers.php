<?php

use App\Support\MeasureFormat;

if (! function_exists('measure_format')) {
    /**
     * Formato de medidas: 60 en lugar de 60,00; 193,5 en lugar de 193,50.
     */
    function measure_format(float|int|string|null $value, int $maxDecimals = 2, string $decimalSeparator = ','): string
    {
        return MeasureFormat::number($value, $maxDecimals, $decimalSeparator);
    }
}
