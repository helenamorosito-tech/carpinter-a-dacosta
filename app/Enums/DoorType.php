<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DoorType: string implements HasLabel
{
    case Abatibles = 'abatibles';
    case Corredizas = 'corredizas';

    public function getLabel(): string
    {
        return match ($this) {
            self::Abatibles => 'Puertas de abrir (abatibles)',
            self::Corredizas => 'Puertas corredizas',
        };
    }
}
