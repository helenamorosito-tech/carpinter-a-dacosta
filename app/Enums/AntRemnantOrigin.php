<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AntRemnantOrigin: string implements HasLabel
{
    case Registered = 'registered';
    case SystemSobrante = 'system_sobrante';

    public function getLabel(): string
    {
        return match ($this) {
            self::Registered => 'Pieza registrada',
            self::SystemSobrante => 'Sobrante de mueble (sistema)',
        };
    }
}
