<?php

namespace App\Models;

use App\Enums\PlacementType;
use App\Services\Carpentry\PresetCutCalculator;
use App\Support\BoardMaterialOptions;
use App\Support\FurnitureTypeCatalog;
use App\Support\MachiningOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FurniturePreset extends Model
{
    use HasFactory;

    public const DEFAULT_DISCOUNT_CM = 6.5;

    public const DEFAULT_SIDE_B_DISCOUNT_CM = 7.0;

    public const DEFAULT_CORREDERAS_SHELF_OFFSET_CM = 9.5;

    public const DEFAULT_SHELF_DEPTH_OFFSET_CM = 9.5;

    /** Ancho balda maletero = ancho global − este descuento */
    public const DEFAULT_TRUNK_SHELF_WIDTH_DISCOUNT_CM = 6.5;

    /** Alto laterales interiores = alto global − este descuento */
    public const DEFAULT_INTERIOR_LATERAL_HEIGHT_OFFSET_CM = 13.0;

    /** Ancho balda adicional = ancho global − este descuento */
    public const DEFAULT_ADDITIONAL_SHELF_WIDTH_OFFSET_CM = 13.0;

    /** Profundidad balda maletero = profundidad global − este descuento */
    public const DEFAULT_TRUNK_SHELF_DEPTH_OFFSET_CM = 9.0;

    public const DEFAULT_BOARD_WIDTH_CM = 122.0;

    public const DEFAULT_BOARD_HEIGHT_CM = 244.0;

    public const DEFAULT_BACK_BOARD_THICKNESS_MM = 10;

    public const DEFAULT_BACK_BOARD_QUANTITY = 1;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'opening_height_m',
        'opening_width_m',
        'opening_depth_cm',
        'global_height_cm',
        'global_width_cm',
        'global_depth_cm',
        'furniture_type',
        'side_type',
        'configuration',
        'board_width_cm',
        'board_height_cm',
        'selected_boards',
        'structure_board_thickness_mm',
        'back_board_thickness_mm',
        'back_board_quantity',
        'calculated_boards_count',
        'pieces',
        'cut_plan',
        'svg_diagram',
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
            'global_height_cm' => 'decimal:2',
            'global_width_cm' => 'decimal:2',
            'global_depth_cm' => 'decimal:2',
            'furniture_type' => 'string',
            'side_type' => PlacementType::class,
            'configuration' => 'array',
            'board_width_cm' => 'decimal:2',
            'board_height_cm' => 'decimal:2',
            'selected_boards' => 'array',
            'structure_board_thickness_mm' => 'integer',
            'back_board_thickness_mm' => 'integer',
            'back_board_quantity' => 'integer',
            'calculated_boards_count' => 'integer',
            'pieces' => 'array',
            'cut_plan' => 'array',
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
            'shelf_count' => 1,
            'shelf_depth_cm' => round(max(0, $openingDepthCm - self::DEFAULT_SHELF_DEPTH_OFFSET_CM), 2),
            'shelf_depths_cm' => [],
            'interior_shelves_count' => 0,
            'interior_shelf_measure_cm' => null,
            'drawers_count' => 0,
            'drawer_height_cm' => null,
            'drawer_width_cm' => null,
            'drawer_depth_cm' => null,
            'hanging_bars_count' => 0,
            'hanging_bar_length_cm' => null,
            'rear_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'trunk_shelf_discount_cm' => self::DEFAULT_DISCOUNT_CM,
            'trunk_shelf_length_cm' => null,
            'trunk_shelf_depth_cm' => null,
            'trunk_shelf_board_mm' => 16,
            'additional_shelves' => [],
            'interior_laterals' => [],
            'costado_visto_izquierda' => true,
            'costado_visto_derecha' => false,
            'lateral_interior_depths_cm' => [],
            'machining' => MachiningOptions::defaults(),
            'machining_positions' => [
                'lateral_pins' => 6,
                'division_pins' => 8,
            ],
            'machining_portabaldas_qty' => null,
            'machining_soporte_lateral_qty' => null,
            'machining_suplemento_height_cm' => 6,
            'machining_extra' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareDataForSave(array $data): array
    {
        $configuration = $data['configuration'] ?? [];
        $furnitureTypeRaw = $data['furniture_type'] ?? null;
        if (is_array($furnitureTypeRaw)) {
            $furnitureTypeRaw = array_values(array_filter($furnitureTypeRaw, fn (mixed $value): bool => filled($value)))[0] ?? null;
            $data['furniture_type'] = $furnitureTypeRaw;
        }
        $furnitureTypeLabel = FurnitureTypeCatalog::label($furnitureTypeRaw);
        $openingDepthCm = (float) ($data['opening_depth_cm'] ?? 60);

        $globalHeight = (float) ($data['global_height_cm'] ?? (((float) ($data['opening_height_m'] ?? 0)) * 100));
        $globalDepth = (float) ($data['global_depth_cm'] ?? $openingDepthCm);
        $globalWidth = (float) ($data['global_width_cm'] ?? (((float) ($data['opening_width_m'] ?? 0)) * 100));

        $configuration['interior_laterals'] = self::normalizeInteriorLaterals($configuration, $globalHeight);
        $configuration['interior_laterals_count'] = count($configuration['interior_laterals']);
        $configuration['additional_shelves'] = self::normalizeAdditionalShelves(
            $configuration,
            $globalDepth,
            $globalWidth,
        );
        $configuration['shelf_count'] = 1 + count($configuration['additional_shelves']);
        $configuration['shelf_depths_cm'] = self::normalizeNumericList(
            $configuration['shelf_depths_cm'] ?? [],
            $configuration['shelf_count'],
            max(0, $globalDepth - self::DEFAULT_CORREDERAS_SHELF_OFFSET_CM),
        );

        unset(
            $configuration['additional_shelves_count'],
            $configuration['additional_shelf_width_cm'],
            $configuration['additional_shelf_depth_cm'],
            $configuration['additional_shelf_board_mm'],
            $configuration['interior_lateral_board_choice'],
            $configuration['interior_lateral_board_mm'],
            $configuration['lateral_interior_depths_cm'],
        );
        $configuration = self::normalizeHangingBarLengthCm($configuration);
        $configuration['machining_extra'] = MachiningOptions::extras($configuration);
        $data['configuration'] = $configuration;
        $data['default_shelf_count'] = (int) $configuration['shelf_count'];
        $data['board_width_cm'] = min(
            (float) ($data['board_width_cm'] ?? self::DEFAULT_BOARD_WIDTH_CM),
            self::DEFAULT_BOARD_WIDTH_CM,
        );
        $data['board_height_cm'] = min(
            (float) ($data['board_height_cm'] ?? self::DEFAULT_BOARD_HEIGHT_CM),
            self::DEFAULT_BOARD_HEIGHT_CM,
        );
        $data['structure_board_thickness_mm'] = (int) ($data['structure_board_thickness_mm'] ?? 16);
        $data['back_board_thickness_mm'] = (int) ($data['back_board_thickness_mm'] ?? self::DEFAULT_BACK_BOARD_THICKNESS_MM);
        if ($data['back_board_thickness_mm'] <= 0) {
            $data['back_board_thickness_mm'] = self::DEFAULT_BACK_BOARD_THICKNESS_MM;
        }
        // El formulario usa Radio (string); en BD se guarda como lista JSON.
        $data['selected_boards'] = BoardMaterialOptions::remappedToThickness(
            self::selectedBoardsAsList($data['selected_boards'] ?? null),
            $data['structure_board_thickness_mm'],
        );
        $data['default_shelf_thickness_cm'] = $data['structure_board_thickness_mm'] / 10;

        $calculation = app(PresetCutCalculator::class)->calculate($data);
        $data['pieces'] = $calculation['pieces'];
        $data['cut_plan'] = $calculation['cut_plan'];
        $data['svg_diagram'] = $calculation['svg_diagram'];
        $data['calculated_boards_count'] = $calculation['calculated_boards_count'];
        $data['back_board_quantity'] = max(
            self::DEFAULT_BACK_BOARD_QUANTITY,
            (int) ($data['back_board_quantity'] ?? self::DEFAULT_BACK_BOARD_QUANTITY),
        );
        $data = self::roundDecimalFields($data);

        if (empty($data['name']) && filled($furnitureTypeLabel)) {
            $globalHeight = (float) ($data['global_height_cm'] ?? (((float) ($data['opening_height_m'] ?? 0)) * 100));
            $globalWidth = (float) ($data['global_width_cm'] ?? (((float) ($data['opening_width_m'] ?? 0)) * 100));
            $globalDepth = (float) ($data['global_depth_cm'] ?? $openingDepthCm);

            $data['name'] = sprintf(
                '%s — %s×%s×%s cm',
                $furnitureTypeLabel,
                number_format($globalHeight, 0, ',', ''),
                number_format($globalWidth, 0, ',', ''),
                number_format($globalDepth, 0, ',', ''),
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

        $left = (bool) ($configuration['costado_visto_izquierda'] ?? false);
        $right = (bool) ($configuration['costado_visto_derecha'] ?? false);

        if ($left === $right) {
            $configuration['costado_visto_izquierda'] = true;
            $configuration['costado_visto_derecha'] = false;
        }

        $configuration = self::normalizeHangingBarLengthCm($configuration);
        $configuration['machining_extra'] = MachiningOptions::extras($configuration);

        $data['configuration'] = $configuration;
        $furnitureType = FurnitureTypeCatalog::normalizeSlug($data['furniture_type'] ?? null);
        // El CheckboxList necesita una lista; en la base el tipo es un solo texto.
        $data['furniture_type'] = filled($furnitureType) ? [$furnitureType] : [];
        $data['side_type'] ??= PlacementType::EntreParedes->value;
        $data['_side_paredes'] = ($data['side_type'] ?? '') === PlacementType::EntreParedes->value;
        $data['_side_costado_visto'] = ($data['side_type'] ?? '') === PlacementType::CostadoVisto->value;
        $data['board_width_cm'] ??= self::DEFAULT_BOARD_WIDTH_CM;
        $data['board_height_cm'] ??= self::DEFAULT_BOARD_HEIGHT_CM;
        $data['structure_board_thickness_mm'] ??= 16;
        $data['back_board_thickness_mm'] ??= self::DEFAULT_BACK_BOARD_THICKNESS_MM;
        $selectedList = BoardMaterialOptions::remappedToThickness(
            self::selectedBoardsAsList($data['selected_boards'] ?? null),
            (int) $data['structure_board_thickness_mm'],
        );
        // Radio de Filament espera un string, no el JSON array de BD.
        $data['selected_boards'] = $selectedList[0] ?? BoardMaterialOptions::defaultSelected()[0];
        $data['back_board_quantity'] ??= self::DEFAULT_BACK_BOARD_QUANTITY;

        if (empty($data['global_height_cm']) && ! empty($data['opening_height_m'])) {
            $data['global_height_cm'] = round((float) $data['opening_height_m'] * 100, 2);
        }

        if (empty($data['global_width_cm']) && ! empty($data['opening_width_m'])) {
            $data['global_width_cm'] = round((float) $data['opening_width_m'] * 100, 2);
        }

        if (empty($data['global_depth_cm']) && ! empty($data['opening_depth_cm'])) {
            $data['global_depth_cm'] = (float) $data['opening_depth_cm'];
        }

        $globalHeight = (float) ($data['global_height_cm'] ?? 0);
        $globalWidth = (float) ($data['global_width_cm'] ?? 0);
        $globalDepth = (float) ($data['global_depth_cm'] ?? 0);
        $configuration['trunk_shelf_board_mm'] = (int) ($configuration['trunk_shelf_board_mm'] ?? 16);

        if (empty($configuration['trunk_shelf_length_cm']) && $globalWidth > 0) {
            $configuration['trunk_shelf_length_cm'] = round(
                max(0, $globalWidth - self::DEFAULT_TRUNK_SHELF_WIDTH_DISCOUNT_CM),
                2,
            );
        }

        if (empty($configuration['trunk_shelf_depth_cm']) && $globalDepth > 0) {
            $configuration['trunk_shelf_depth_cm'] = round(
                max(0, $globalDepth - self::DEFAULT_TRUNK_SHELF_DEPTH_OFFSET_CM),
                2,
            );
        }

        $configuration['additional_shelves'] = self::normalizeAdditionalShelves(
            $configuration,
            $globalDepth,
            $globalWidth,
        );
        $configuration['shelf_count'] = 1 + count($configuration['additional_shelves']);
        $configuration['interior_laterals'] = self::normalizeInteriorLaterals($configuration, $globalHeight);
        $configuration['interior_laterals_count'] = count($configuration['interior_laterals']);

        unset(
            $configuration['additional_shelves_count'],
            $configuration['additional_shelf_width_cm'],
            $configuration['additional_shelf_depth_cm'],
            $configuration['additional_shelf_board_mm'],
            $configuration['interior_lateral_board_choice'],
            $configuration['interior_lateral_board_mm'],
            $configuration['lateral_interior_depths_cm'],
        );

        $data['configuration'] = $configuration;

        return $data;
    }

    /**
     * Unifica la longitud de barra de colgar en cm (migra el antiguo campo en metros).
     *
     * @param  array<string, mixed>  $configuration
     * @return array<string, mixed>
     */
    public static function normalizeHangingBarLengthCm(array $configuration): array
    {
        $lengthCm = $configuration['hanging_bar_length_cm'] ?? null;

        if (($lengthCm === null || $lengthCm === '') && isset($configuration['hanging_bar_measure_m'])) {
            $meters = (float) $configuration['hanging_bar_measure_m'];
            if ($meters > 0) {
                $lengthCm = round($meters * 100, 2);
            }
        }

        if ($lengthCm !== null && $lengthCm !== '') {
            $configuration['hanging_bar_length_cm'] = round((float) $lengthCm, 2);
        } else {
            $configuration['hanging_bar_length_cm'] = null;
        }

        unset($configuration['hanging_bar_measure_m']);

        return $configuration;
    }

    /**
     * Grosor del tablero (mm) desde choice + mm de un ítem o config legacy.
     *
     * @param  array<string, mixed>  $item
     */
    public static function resolveInteriorLateralBoardMm(array $item): int
    {
        $choice = (string) ($item['board_choice'] ?? $item['interior_lateral_board_choice'] ?? '');

        if ($choice === 'other') {
            $custom = (int) ($item['board_mm'] ?? $item['interior_lateral_board_mm'] ?? 0);

            return $custom > 0 ? $custom : 16;
        }

        if ($choice === '1' || $choice === '16') {
            return (int) $choice;
        }

        $mm = (int) ($item['board_mm'] ?? $item['interior_lateral_board_mm'] ?? 16);

        return $mm > 0 ? $mm : 16;
    }

    /**
     * Listado de laterales interiores. Migra el formato antiguo (count + alturas + grosor global).
     *
     * @param  array<string, mixed>  $configuration
     * @return list<array{height_cm: float, board_choice: string, board_mm: int}>
     */
    public static function normalizeInteriorLaterals(array $configuration, float $globalHeightCm = 0): array
    {
        $defaultHeight = $globalHeightCm > 0
            ? round(max(0, $globalHeightCm - self::DEFAULT_INTERIOR_LATERAL_HEIGHT_OFFSET_CM), 2)
            : 0.0;

        $laterals = $configuration['interior_laterals'] ?? null;

        if (is_array($laterals) && $laterals !== []) {
            $normalized = [];

            foreach ($laterals as $lateral) {
                if (! is_array($lateral)) {
                    continue;
                }

                $height = isset($lateral['height_cm']) && $lateral['height_cm'] !== ''
                    ? round((float) $lateral['height_cm'], 2)
                    : $defaultHeight;

                if ($height <= 0) {
                    continue;
                }

                $boardMm = self::resolveInteriorLateralBoardMm($lateral);
                $choice = in_array($boardMm, [1, 16], true) ? (string) $boardMm : 'other';

                $normalized[] = [
                    'height_cm' => $height,
                    'board_choice' => (string) ($lateral['board_choice'] ?? $choice),
                    'board_mm' => $boardMm,
                ];
            }

            return $normalized;
        }

        // Formato antiguo.
        $count = max(0, (int) ($configuration['interior_laterals_count'] ?? 0));
        if ($count <= 0) {
            return [];
        }

        $heights = is_array($configuration['lateral_interior_depths_cm'] ?? null)
            ? $configuration['lateral_interior_depths_cm']
            : [];
        $legacyBoardMm = self::resolveInteriorLateralBoardMm($configuration);
        $legacyChoice = in_array($legacyBoardMm, [1, 16], true) ? (string) $legacyBoardMm : 'other';
        $normalized = [];

        for ($i = 1; $i <= $count; $i++) {
            $heightKey = $count > 2 ? 1 : $i;
            $height = (float) ($heights[$heightKey] ?? $heights[1] ?? 0);
            if ($height <= 0) {
                $height = $defaultHeight;
            }
            if ($height <= 0) {
                continue;
            }

            $normalized[] = [
                'height_cm' => round($height, 2),
                'board_choice' => $legacyChoice,
                'board_mm' => $legacyBoardMm,
            ];
        }

        return $normalized;
    }

    /**
     * Ancho balda adicional = ancho global − 13 cm.
     *
     * @param  array<string, mixed>  $configuration
     */
    public static function defaultAdditionalShelfWidthCm(float $globalWidthCm, array $configuration = []): float
    {
        if ($globalWidthCm <= 0) {
            return 0.0;
        }

        return round(max(0, $globalWidthCm - self::DEFAULT_ADDITIONAL_SHELF_WIDTH_OFFSET_CM), 2);
    }

    /**
     * Profundidad balda adicional = profundidad global − 9 cm.
     */
    public static function defaultAdditionalShelfDepthCm(float $globalDepthCm): float
    {
        if ($globalDepthCm <= 0) {
            return 0.0;
        }

        return round(max(0, $globalDepthCm - self::DEFAULT_TRUNK_SHELF_DEPTH_OFFSET_CM), 2);
    }

    /**
     * Lista de baldas adicionales (una ficha por balda).
     * Migra el formato antiguo count + medidas compartidas.
     *
     * @param  array<string, mixed>  $configuration
     * @return list<array{width_cm: float, depth_cm: float, board_mm: int}>
     */
    public static function normalizeAdditionalShelves(
        array $configuration,
        float $globalDepthCm = 0,
        float $globalWidthCm = 0,
    ): array {
        $defaultDepth = self::defaultAdditionalShelfDepthCm($globalDepthCm);
        $defaultWidth = self::defaultAdditionalShelfWidthCm($globalWidthCm, $configuration);

        $shelves = $configuration['additional_shelves'] ?? null;

        if (is_array($shelves) && $shelves !== []) {
            $normalized = [];

            foreach ($shelves as $shelf) {
                if (! is_array($shelf)) {
                    continue;
                }

                $width = isset($shelf['width_cm']) && $shelf['width_cm'] !== ''
                    ? round((float) $shelf['width_cm'], 2)
                    : $defaultWidth;
                $depth = isset($shelf['depth_cm']) && $shelf['depth_cm'] !== ''
                    ? round((float) $shelf['depth_cm'], 2)
                    : $defaultDepth;
                $boardMm = (int) ($shelf['board_mm'] ?? 16);

                if ($width <= 0 && $depth <= 0) {
                    continue;
                }

                $normalized[] = [
                    'width_cm' => $width > 0 ? $width : $defaultWidth,
                    'depth_cm' => $depth > 0 ? $depth : $defaultDepth,
                    'board_mm' => in_array($boardMm, [10, 16, 19], true) ? $boardMm : 16,
                ];
            }

            return $normalized;
        }

        // Formato antiguo: cantidad + medidas únicas.
        $count = max(0, (int) ($configuration['additional_shelves_count'] ?? 0));
        $width = (float) ($configuration['additional_shelf_width_cm'] ?? 0);
        $depth = (float) ($configuration['additional_shelf_depth_cm'] ?? 0);
        $boardMm = (int) ($configuration['additional_shelf_board_mm'] ?? 16);

        if ($width <= 0) {
            $width = $defaultWidth;
        }

        if ($depth <= 0) {
            $depth = $defaultDepth;
        }

        if ($count <= 0 || $width <= 0 || $depth <= 0) {
            return [];
        }

        $legacy = [];
        for ($i = 0; $i < $count; $i++) {
            $legacy[] = [
                'width_cm' => round($width, 2),
                'depth_cm' => round($depth, 2),
                'board_mm' => in_array($boardMm, [10, 16, 19], true) ? $boardMm : 16,
            ];
        }

        return $legacy;
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
            $normalized[$index] = $value !== null && $value !== ''
                ? round((float) $value, 2)
                : null;
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    protected static function selectedBoardsAsList(mixed $selected): array
    {
        if (is_array($selected)) {
            return array_values(array_filter(array_map(
                static fn (mixed $value): string => (string) $value,
                $selected,
            )));
        }

        if (filled($selected)) {
            return [(string) $selected];
        }

        return BoardMaterialOptions::defaultSelected();
    }

    /**
     * En toda la app solo se admiten 2 decimales en medidas.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function roundDecimalFields(array $data): array
    {
        foreach ([
            'opening_height_m',
            'opening_width_m',
            'opening_depth_cm',
            'global_height_cm',
            'global_width_cm',
            'global_depth_cm',
            'board_width_cm',
            'board_height_cm',
            'default_shelf_thickness_cm',
        ] as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                $data[$key] = round((float) $data[$key], 2);
            }
        }

        $configuration = $data['configuration'] ?? [];
        if (is_array($configuration)) {
            foreach ([
                'base_discount_cm',
                'height_discount_cm',
                'left_side_discount_cm',
                'right_side_discount_cm',
                'side_a_discount_cm',
                'side_b_discount_cm',
                'shelf_depth_cm',
                'interior_shelf_measure_cm',
                'drawer_height_cm',
                'drawer_width_cm',
                'drawer_depth_cm',
                'hanging_bar_length_cm',
                'rear_discount_cm',
                'trunk_shelf_discount_cm',
                'trunk_shelf_length_cm',
                'trunk_shelf_depth_cm',
            ] as $key) {
                if (array_key_exists($key, $configuration) && $configuration[$key] !== null && $configuration[$key] !== '') {
                    $configuration[$key] = round((float) $configuration[$key], 2);
                }
            }

            if (! empty($configuration['additional_shelves']) && is_array($configuration['additional_shelves'])) {
                foreach ($configuration['additional_shelves'] as $index => $shelf) {
                    if (! is_array($shelf)) {
                        continue;
                    }

                    foreach (['width_cm', 'depth_cm'] as $measureKey) {
                        if (isset($shelf[$measureKey]) && $shelf[$measureKey] !== '' && $shelf[$measureKey] !== null) {
                            $configuration['additional_shelves'][$index][$measureKey] = round((float) $shelf[$measureKey], 2);
                        }
                    }
                }
            }

            if (! empty($configuration['interior_laterals']) && is_array($configuration['interior_laterals'])) {
                foreach ($configuration['interior_laterals'] as $index => $lateral) {
                    if (! is_array($lateral)) {
                        continue;
                    }

                    if (isset($lateral['height_cm']) && $lateral['height_cm'] !== '' && $lateral['height_cm'] !== null) {
                        $configuration['interior_laterals'][$index]['height_cm'] = round((float) $lateral['height_cm'], 2);
                    }
                }
            }

            foreach (['shelf_depths_cm', 'lateral_interior_depths_cm'] as $listKey) {
                if (! empty($configuration[$listKey]) && is_array($configuration[$listKey])) {
                    foreach ($configuration[$listKey] as $index => $value) {
                        if ($value !== null && $value !== '') {
                            $configuration[$listKey][$index] = round((float) $value, 2);
                        }
                    }
                }
            }

            $data['configuration'] = $configuration;
        }

        return $data;
    }
}
