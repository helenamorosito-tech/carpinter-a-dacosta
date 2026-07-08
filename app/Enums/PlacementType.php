<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlacementType: string implements HasLabel
{
    case EntreParedes = 'entre_paredes';
    case CostadoVisto = 'costado_visto';

    public function getLabel(): string
    {
        return match ($this) {
            self::EntreParedes => 'Entre paredes',
            self::CostadoVisto => 'Costado visto',
        };
    }
}
