<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BoardThickness: int implements HasLabel
{
    case Mm10 = 10;
    case Mm16 = 16;
    case Mm19 = 19;

    public function getLabel(): string
    {
        return $this->value.' mm';
    }

    public function toCm(): float
    {
        return $this->value / 10;
    }
}
