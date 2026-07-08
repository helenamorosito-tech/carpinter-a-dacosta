<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FurnitureCabinetType: string implements HasLabel
{
    case Armario2PuertasAbrir = 'armario_2_puertas_abrir';
    case Armario3PuertasAbrir = 'armario_3_puertas_abrir';
    case Armario4PuertasAbrir = 'armario_4_puertas_abrir';
    case Armario2PuertasCorrederas = 'armario_2_puertas_correderas';
    case Armario3PuertasCorrederas = 'armario_3_puertas_correderas';
    case Armario4PuertasCorrederas = 'armario_4_puertas_correderas';

    public function getLabel(): string
    {
        return match ($this) {
            self::Armario2PuertasAbrir => 'Armario 2 puertas (de abrir)',
            self::Armario3PuertasAbrir => 'Armario 3 puertas (de abrir)',
            self::Armario4PuertasAbrir => 'Armario 4 puertas (de abrir)',
            self::Armario2PuertasCorrederas => 'Armario 2 puertas (correderas)',
            self::Armario3PuertasCorrederas => 'Armario 3 puertas (correderas)',
            self::Armario4PuertasCorrederas => 'Armario 4 puertas (correderas)',
        };
    }

    public function isCorrederas(): bool
    {
        return match ($this) {
            self::Armario2PuertasCorrederas,
            self::Armario3PuertasCorrederas,
            self::Armario4PuertasCorrederas => true,
            default => false,
        };
    }

    public function doorCount(): int
    {
        return match ($this) {
            self::Armario2PuertasAbrir, self::Armario2PuertasCorrederas => 2,
            self::Armario3PuertasAbrir, self::Armario3PuertasCorrederas => 3,
            self::Armario4PuertasAbrir, self::Armario4PuertasCorrederas => 4,
        };
    }

    public function lateralInteriorFieldCount(): int
    {
        return match ($this->doorCount()) {
            2 => 2,
            3 => 4,
            4 => 6,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function abrirOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $type): bool => $type->isCorrederas())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->getLabel()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function correderasOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $type): bool => $type->isCorrederas())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->getLabel()])
            ->all();
    }

    public static function tryFromState(?string $state): ?self
    {
        if ($state === null) {
            return null;
        }

        return self::tryFrom($state);
    }
}
