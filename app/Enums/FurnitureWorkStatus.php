<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FurnitureWorkStatus: string implements HasColor, HasLabel
{
    case EnProyecto = 'en_proyecto';
    case EnObra = 'en_obra';
    case Terminado = 'terminado';

    public function getLabel(): string
    {
        return match ($this) {
            self::EnProyecto => 'En proyecto',
            self::EnObra => 'En obra',
            self::Terminado => 'Terminado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EnProyecto => 'info',
            self::EnObra => 'warning',
            self::Terminado => 'success',
        };
    }
}
