<?php

namespace App\Models;

use App\Enums\FurnitureCabinetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FurnitureType extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'is_correderas',
        'door_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_correderas' => 'boolean',
            'door_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function createFromLabel(
        string $name,
        bool $isCorrederas = false,
        int $doorCount = 2,
    ): self {
        $name = trim($name);
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'tipo-mueble';
        $suffix = 1;

        while (
            self::query()->where('slug', $slug)->exists()
            || FurnitureCabinetType::tryFrom($slug) !== null
        ) {
            $slug = ($baseSlug !== '' ? $baseSlug : 'tipo-mueble').'-'.$suffix;
            $suffix++;
        }

        return self::query()->create([
            'slug' => $slug,
            'name' => $name,
            'is_correderas' => $isCorrederas,
            'door_count' => max(1, min(6, $doorCount)),
            'is_active' => true,
        ]);
    }
}
