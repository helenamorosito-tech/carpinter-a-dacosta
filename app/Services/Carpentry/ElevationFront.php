<?php

namespace App\Services\Carpentry;

use App\Support\FurnitureTypeCatalog;

class ElevationFront
{
    /**
     * @return array{doors: int, widths_mm: list<int>, verticals: list<int>}
     */
    public static function layout(mixed $furnitureType, string $name, int $modules, float $widthCm): array
    {
        $modules = max(1, $modules);
        $doors = self::doorCount($furnitureType, $name);
        if ($doors < 1) {
            $doors = $modules;
        }

        $doors = max($doors, $modules);
        $base = intdiv($doors, $modules);
        $extra = $doors % $modules;
        $shares = array_fill(0, $modules, $base);

        for ($index = $modules - 1; $index >= 0 && $extra > 0; $index--) {
            $shares[$index]++;
            $extra--;
        }

        $sum = max(1, array_sum($shares));
        $totalMm = (int) round($widthCm * 10);
        $widths = [];
        $assigned = 0;

        foreach ($shares as $index => $share) {
            if ($index === $modules - 1) {
                $widths[] = max(1, $totalMm - $assigned);

                continue;
            }

            $mm = (int) round($totalMm * $share / $sum);
            $widths[] = max(1, $mm);
            $assigned += $mm;
        }

        return [
            'doors' => $doors,
            'widths_mm' => $widths,
            'verticals' => array_map(fn (int $share): int => max(0, $share - 1), $shares),
        ];
    }

    public static function doorCount(mixed $furnitureType, string $name): int
    {
        $fromType = FurnitureTypeCatalog::doorCount($furnitureType);
        if ($fromType > 0) {
            return $fromType;
        }

        if (preg_match('/(\d+)\s*puertas/u', mb_strtolower($name), $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        return 0;
    }

    public static function typeLabel(mixed $furnitureType, string $name): string
    {
        $label = FurnitureTypeCatalog::label($furnitureType);
        if (filled($label)) {
            return $label;
        }

        $doors = self::doorCount(null, $name);
        if ($doors < 1) {
            return '—';
        }

        $style = str_contains(mb_strtolower($name), 'corredera') ? 'correderas' : 'de abrir';

        return "Armario {$doors} puertas ({$style})";
    }
}
