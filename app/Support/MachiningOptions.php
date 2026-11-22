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
}
