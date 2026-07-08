<?php

namespace App\Models;

use App\Enums\DoorType;
use App\Enums\PlacementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FurnitureProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'furniture_preset_id',
        'material_id',
        'name',
        'reference',
        'height_cm',
        'width_cm',
        'depth_cm',
        'placement_type',
        'door_type',
        'shelf_count',
        'shelf_thickness_cm',
        'module_count',
        'door_count',
        'effective_width_cm',
        'pieces',
        'cut_plan',
        'svg_diagram',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'height_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'depth_cm' => 'decimal:2',
            'placement_type' => PlacementType::class,
            'door_type' => DoorType::class,
            'shelf_count' => 'integer',
            'shelf_thickness_cm' => 'decimal:2',
            'module_count' => 'integer',
            'door_count' => 'integer',
            'effective_width_cm' => 'decimal:2',
            'pieces' => 'array',
            'cut_plan' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preset(): BelongsTo
    {
        return $this->belongsTo(FurniturePreset::class, 'furniture_preset_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
