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
     * @return array<string, string>
     */
    public static function defaultSelected(): array
    {
        return [
            self::optionKey(BoardColor::Blanco, BoardThickness::Mm16),
        ];
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
