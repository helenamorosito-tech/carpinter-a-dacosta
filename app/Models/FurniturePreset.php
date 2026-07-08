<?php

namespace App\Models;

use App\Enums\FurnitureCabinetType;
use App\Enums\PlacementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FurniturePreset extends Model
{
    use HasFactory;

    public const DEFAULT_DISCOUNT_CM = 6.5;

    public const DEFAULT_SIDE_B_DISCOUNT_CM = 7.0;

    public const DEFAULT_CORREDERAS_SHELF_OFFSET_CM = 9.0;

    public const DEFAULT_SHELF_DEPTH_OFFSET_CM = 0.5;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'opening_height_m',
        'opening_width_m',
        'opening_depth_cm',
        'furniture_type',
        'side_type',
        'configuration',
        'default_shelf_count',
        'default_shelf_thickness_cm',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_height_m' => 'decimal:2',
            'opening_width_m' => 'decimal:2',
            'opening_depth_cm' => 'decimal:2',
            'furniture_type' => FurnitureCabinetType::class,
            'side_type' => PlacementType::class,
            'configuration' => 'array',
            'default_shelf_count' => 'integer',
            'default_shelf_thickness_cm' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(FurnitureProject::class);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultConfiguration(float $openingDepthCm = 60): array
    {
        return [
            'module_count' => 1,
            'base_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'height_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'left_side_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'right_side_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'side_a_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'side_b_discount_cm' => self::DEFAULT_SIDE_B_DISCOUNT_CM,
            'shelf_count' => 2,
            'shelf_depth_cm' => max(0, $openingDepthCm - self::DEFAULT_SHELF_DEPTH_OFFSET_CM),
            'shelf_depths_cm' => [],
            'interior_shelves_count' => 0,
            'interior_shelf_measure_cm' => null,
            'drawers_count' => 0,
            'drawer_height_cm' => null,
            'drawer_width_cm' => null,
            'hanging_bars_count' => 0,
            'hanging_bar_measure_m' => null,
            'rear_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'trunk_shelf_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'lateral_interior_depths_cm' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareDataForSave(array $data): array
    {
        $configuration = $data['configuration'] ?? [];
        $furnitureType = FurnitureCabinetType::tryFrom($data['furniture_type'] ?? '');
        $openingDepthCm = (float) ($data['opening_depth_cm'] ?? 60);

        $configuration['shelf_depths_cm'] = self::normalizeNumericList(
            $configuration['shelf_depths_cm'] ?? [],
            (int) ($configuration['shelf_count'] ?? 0),
            max(0, $openingDepthCm - self::DEFAULT_CORREDERAS_SHELF_OFFSET_CM),
        );

        if ($furnitureType) {
            $configuration['lateral_interior_depths_cm'] = self::normalizeNumericList(
                $configuration['lateral_interior_depths_cm'] ?? [],
                $furnitureType->lateralInteriorFieldCount(),
            );
        }

        $data['configuration'] = $configuration;
        $data['default_shelf_count'] = (int) ($configuration['shelf_count'] ?? 2);

        if (empty($data['name']) && $furnitureType) {
            $data['name'] = sprintf(
                '%s — %s×%sm × %scm',
                $furnitureType->getLabel(),
                number_format((float) ($data['opening_height_m'] ?? 0), 2, ',', ''),
                number_format((float) ($data['opening_width_m'] ?? 0), 2, ',', ''),
                number_format($openingDepthCm, 0, ',', ''),
            );
        }

        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        }

        $data['is_active'] ??= true;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareDataForFill(array $data): array
    {
        $configuration = array_replace(
            self::defaultConfiguration((float) ($data['opening_depth_cm'] ?? 60)),
            $data['configuration'] ?? [],
        );

        $data['configuration'] = $configuration;
        $data['furniture_type'] ??= null;
        $data['side_type'] ??= PlacementType::EntreParedes->value;
        $data['_side_paredes'] = ($data['side_type'] ?? '') === PlacementType::EntreParedes->value;
        $data['_side_costado_visto'] = ($data['side_type'] ?? '') === PlacementType::CostadoVisto->value;

        return $data;
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<int, float|null>
     */
    protected static function normalizeNumericList(array $values, int $count, ?float $default = null): array
    {
        $normalized = [];

        for ($index = 1; $index <= $count; $index++) {
            $value = $values[$index] ?? $values[$index - 1] ?? $default;
            $normalized[$index] = $value !== null && $value !== '' ? (float) $value : null;
        }

        return $normalized;
    }
}
