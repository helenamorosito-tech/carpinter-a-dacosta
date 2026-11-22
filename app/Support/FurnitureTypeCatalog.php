<?php

namespace App\Support;

use App\Enums\FurnitureCabinetType;
use App\Models\FurnitureType;

class FurnitureTypeCatalog
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [
            ...FurnitureCabinetType::abrirOptions(),
            ...FurnitureCabinetType::correderasOptions(),
        ];

        $custom = FurnitureType::query()
            ->active()
            ->orderBy('name')
            ->get(['slug', 'name']);

        foreach ($custom as $type) {
            $options[$type->slug] = $type->name;
        }

        return $options;
    }

    /**
     * @return array{slug: string, label: string, is_correderas: bool, door_count: int}|null
     */
    public static function resolve(mixed $state): ?array
    {
        $slug = self::normalizeSlug($state);
        if ($slug === null) {
            return null;
        }

        $enum = FurnitureCabinetType::tryFrom($slug);
        if ($enum !== null) {
            return [
                'slug' => $enum->value,
                'label' => $enum->getLabel(),
                'is_correderas' => $enum->isCorrederas(),
                'door_count' => $enum->doorCount(),
            ];
        }

        $custom = FurnitureType::query()->where('slug', $slug)->first();
        if ($custom !== null) {
            return [
                'slug' => $custom->slug,
                'label' => $custom->name,
                'is_correderas' => (bool) $custom->is_correderas,
                'door_count' => (int) $custom->door_count,
            ];
        }

        return [
            'slug' => $slug,
            'label' => $slug,
            'is_correderas' => str_contains($slug, 'corredera'),
            'door_count' => 2,
        ];
    }

    public static function label(mixed $state): ?string
    {
        return self::resolve($state)['label'] ?? null;
    }

    public static function isCorrederas(mixed $state): bool
    {
        return self::resolve($state)['is_correderas'] ?? false;
    }

    public static function doorCount(mixed $state): int
    {
        return self::resolve($state)['door_count'] ?? 0;
    }

    public static function normalizeSlug(mixed $state): ?string
    {
        if (is_array($state)) {
            $selected = array_values(array_filter($state, fn (mixed $value): bool => filled($value)));
            $state = $selected[0] ?? null;
        }

        if ($state instanceof FurnitureCabinetType) {
            return $state->value;
        }

        if (! is_string($state) || ! filled($state)) {
            return null;
        }

        return $state;
    }
}
