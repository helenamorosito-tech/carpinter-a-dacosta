<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MaterialType: string implements HasLabel
{
    case Textil = 'textil';
    case Machembrado = 'machembrado';
    case CartonPiedra = 'carton_piedra';

    public function getLabel(): string
    {
        return match ($this) {
            self::Textil => 'Textil',
            self::Machembrado => 'Machembrado',
            self::CartonPiedra => 'Cartón piedra',
        };
    }
}
