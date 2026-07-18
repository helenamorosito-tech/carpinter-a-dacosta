<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BoardColor: string implements HasLabel
{
    case Blanco = 'blanco';
    case Sapelly = 'sapelly';
    case Roble = 'roble';
    case Textil = 'textil';

    public function getLabel(): string
    {
        return match ($this) {
            self::Blanco => 'Blanco',
            self::Sapelly => 'Sapelly',
            self::Roble => 'Roble',
            self::Textil => 'Textil',
        };
    }
}
