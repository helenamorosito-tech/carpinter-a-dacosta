<?php

namespace App\Support;

class MachiningOptions
{
    public const LATERALES = 'laterales';

    public const BASE = 'base';

    public const TECHO = 'techo';

    public const MALETERO = 'balda_maletero';

    public const DIVISION = 'division';

    public const PORTABALDAS = 'portabaldas';

    public const TORNILLO = 'tornillo_ensamblaje';

    public const SOPORTE_LATERAL = 'soporte_lateral';

    public const SOPORTE_CENTRAL = 'soporte_central';

    public const SUPLEMENTOS = 'suplementos_base';

    public const FRENTES = 'frentes_sin_tirador';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::LATERALES => 'Laterales (agujeros de balda y esquinas)',
            self::BASE => 'Base',
            self::TECHO => 'Techo',
            self::MALETERO => 'Balda maletero (marca 1/2)',
            self::DIVISION => 'División / lateral interior',
            self::PORTABALDAS => 'Portabaldas',
            self::TORNILLO => 'Tornillo de ensamblaje',
            self::SOPORTE_LATERAL => 'Soporte lateral',
            self::SOPORTE_CENTRAL => 'Soporte central',
            self::SUPLEMENTOS => 'Suplementos para base',
            self::FRENTES => 'Frentes sin tirador',
        ];
    }

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return array_keys(self::options());
    }

    /**
     * @param  list<string>|null  $selected
     */
    public static function enabled(?array $selected, string $key): bool
    {
        return in_array($key, $selected ?? [], true);
    }

    /**
     * Distancias en mm desde el canto. Vacío conserva el esquema actual del dibujo.
     *
     * @return array{
     *     corner_x_mm: float|null,
     *     corner_y_mm: float|null,
     *     lateral_pins: int,
     *     division_pins: int,
     *     pin_start_mm: float|null,
     *     pin_step_mm: float|null,
     *     maletero_1_mm: float|null,
     *     maletero_2_mm: float|null,
     *     maletero_3_mm: float|null,
     *     maletero_half_mm: float|null
     * }
     */
    public static function positions(?array $configuration): array
    {
        $raw = is_array($configuration['machining_positions'] ?? null)
            ? $configuration['machining_positions']
            : [];

        return [
            'corner_x_mm' => self::millimetres($raw['corner_x_mm'] ?? null),
            'corner_y_mm' => self::millimetres($raw['corner_y_mm'] ?? null),
            'lateral_pins' => max(2, (int) ($raw['lateral_pins'] ?? 6)),
            'division_pins' => max(2, (int) ($raw['division_pins'] ?? 8)),
            'pin_start_mm' => self::millimetres($raw['pin_start_mm'] ?? null),
            'pin_step_mm' => self::millimetres($raw['pin_step_mm'] ?? null),
            'maletero_1_mm' => self::millimetres($raw['maletero_1_mm'] ?? null),
            'maletero_2_mm' => self::millimetres($raw['maletero_2_mm'] ?? null),
            'maletero_3_mm' => self::millimetres($raw['maletero_3_mm'] ?? null),
            'maletero_half_mm' => self::millimetres($raw['maletero_half_mm'] ?? null),
        ];
    }

    private static function millimetres(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return $number > 0 ? $number : null;
    }
}
