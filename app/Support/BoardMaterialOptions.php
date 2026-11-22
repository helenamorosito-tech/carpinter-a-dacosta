<?php

namespace App\Support;

use App\Enums\BoardColor;
use App\Enums\BoardThickness;

class BoardMaterialOptions
{
    public static function optionKey(BoardColor $color, BoardThickness $thickness): string
    {
        return $color->value.'_'.$thickness->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (BoardColor::cases() as $color) {
            foreach (BoardThickness::cases() as $thickness) {
                $key = self::optionKey($color, $thickness);
                $options[$key] = sprintf('%s — %s', $color->getLabel(), $thickness->getLabel());
            }
        }

        return $options;
    }

    /**
     * Material options limited to a single board thickness (for structure listing).
     *
     * @return array<string, string>
     */
    public static function optionsForThickness(int $thicknessMm): array
    {
        $thickness = BoardThickness::tryFrom($thicknessMm) ?? BoardThickness::Mm16;
        $options = [];

        foreach (BoardColor::cases() as $color) {
            $key = self::optionKey($color, $thickness);
            $options[$key] = sprintf('%s — %s', $color->getLabel(), $thickness->getLabel());
        }

        return $options;
    }

    /**
     * Keep selected colors, rewriting keys to the given thickness.
     *
     * @param  list<string>|null  $keys
     * @return list<string>
     */
    public static function remappedToThickness(?array $keys, int $thicknessMm): array
    {
        $thickness = BoardThickness::tryFrom($thicknessMm) ?? BoardThickness::Mm16;
        $remapped = [];

        foreach ($keys ?? [] as $key) {
            $item = self::parse((string) $key);

            if ($item) {
                $remapped[] = self::optionKey($item['color'], $thickness);
            }
        }

        if ($remapped === []) {
            return [self::optionKey(BoardColor::Blanco, $thickness)];
        }

        return array_values(array_unique($remapped));
    }

    /**
     * @return list<string>
     */
    public static function defaultSelected(): array
    {
        return [
            self::optionKey(BoardColor::Blanco, BoardThickness::Mm16),
        ];
    }

    /**
     * Thickness in mm from the first selected board (preferring 16 mm if present).
     *
     * @param  list<string>|null  $keys
     */
    public static function primaryThicknessMm(?array $keys): int
    {
        return (int) round(self::primaryThicknessCm($keys) * 10);
    }

    public static function parse(string $key): ?array
    {
        if (! preg_match('/^([a-z]+)_(\d+)$/', $key, $matches)) {
            return null;
        }

        $color = BoardColor::tryFrom($matches[1]);
        $thickness = BoardThickness::tryFrom((int) $matches[2]);

        if (! $color || ! $thickness) {
            return null;
        }

        return [
            'color' => $color,
            'thickness' => $thickness,
            'key' => $key,
            'label' => sprintf('%s — %s', $color->getLabel(), $thickness->getLabel()),
            'thickness_cm' => $thickness->toCm(),
        ];
    }

    /**
     * @param  list<string>|null  $keys
     * @return list<array{color: BoardColor, thickness: BoardThickness, key: string, label: string, thickness_cm: float}>
     */
    public static function parseMany(?array $keys): array
    {
        $parsed = [];

        foreach ($keys ?? [] as $key) {
            $item = self::parse((string) $key);

            if ($item) {
                $parsed[] = $item;
            }
        }

        return $parsed;
    }

    /**
     * Preferred thickness for cutting main pieces (first selected, preferring 16 mm).
     *
     * @param  list<string>|null  $keys
     */
    public static function primaryThicknessCm(?array $keys): float
    {
        $parsed = self::parseMany($keys);

        if ($parsed === []) {
            return BoardThickness::Mm16->toCm();
        }

        foreach ($parsed as $item) {
            if ($item['thickness'] === BoardThickness::Mm16) {
                return $item['thickness_cm'];
            }
        }

        return $parsed[0]['thickness_cm'];
    }
}
