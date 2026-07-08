<?php

namespace App\Models;

use App\Enums\MaterialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'width_cm',
        'height_cm',
        'thickness_cm',
        'quantity',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'thickness_cm' => 'decimal:2',
            'quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(FurnitureProject::class);
    }

    public function getDimensionsLabelAttribute(): string
    {
        return sprintf(
            '%s × %s cm (grosor %s cm)',
            $this->width_cm,
            $this->height_cm,
            $this->thickness_cm,
        );
    }
}
