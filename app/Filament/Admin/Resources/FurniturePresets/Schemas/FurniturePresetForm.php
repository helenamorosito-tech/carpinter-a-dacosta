<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Schemas;

use App\Enums\FurnitureCabinetType;
use App\Enums\PlacementType;
use App\Models\FurniturePreset;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class FurniturePresetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Tamaño de Hueco')
                        ->description('Indica las medidas del hueco disponible')
                        ->schema([
                            Section::make('Medidas del hueco')
                                ->collapsible()
                                ->schema([
                                TextInput::make('opening_height_m')
                                    ->label('Alto del hueco (m)')
                                    ->numeric()
                                    ->required()
                                    ->default(2.50)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix('m'),
                                TextInput::make('opening_width_m')
                                    ->label('Ancho del hueco (m)')
                                    ->numeric()
                                    ->required()
                                    ->default(2.00)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix('m'),
                                TextInput::make('opening_depth_cm')
                                    ->label('Profundidad del hueco (cm)')
                                    ->numeric()
                                    ->required()
                                    ->default(60)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->suffix('cm')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                                        self::syncShelfDepthDefaults($set, $get, (float) ($state ?? 60));
                                    }),
                            ])->columns(2),
                        ]),
                    Step::make('Tipo de mueble')
                        ->description('Selecciona el armario y configura descuentos y accesorios')
                        ->schema([
                            Section::make('Tipo de mueble')
                                ->collapsible()
                                ->schema([
                                    Select::make('furniture_type')
                                        ->label('Tipo de mueble')
                                        ->options([
                                            'Puertas de abrir' => FurnitureCabinetType::abrirOptions(),
                                            'Puertas correderas' => FurnitureCabinetType::correderasOptions(),
                                        ])
                                        ->native(false)
                                        ->required()
                                        ->live()
                                        ->helperText(new HtmlString(
                                            '<span class="text-amber-600 dark:text-amber-400">Las opciones de <strong>puertas correderas</strong> aparecen en el segundo grupo del desplegable.</span>'
                                        )),
                                ]),
                            Section::make('Configuración general')
                                ->collapsible()
                                ->columns(3)
                                ->schema([
                                    TextInput::make('configuration.module_count')
                                        ->label('Número de módulos')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required(),
                                    TextInput::make('configuration.base_discount_cm')
                                        ->label('Descuento de base (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                    TextInput::make('configuration.height_discount_cm')
                                        ->label('Descuento de altura (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                ]),
                            Section::make('Tipo de lado')
                                ->collapsible()
                                ->columns(2)
                                ->schema([
                                    Checkbox::make('_side_paredes')
                                        ->label('Paredes')
                                        ->live()
                                        ->dehydrated(false)
                                        ->default(true)
                                        ->afterStateUpdated(function (bool $state, callable $set): void {
                                            if ($state) {
                                                $set('side_type', PlacementType::EntreParedes->value);
                                                $set('_side_costado_visto', false);

                                                return;
                                            }

                                            $set('_side_costado_visto', true);
                                            $set('side_type', PlacementType::CostadoVisto->value);
                                        }),
                                    Checkbox::make('_side_costado_visto')
                                        ->label('Costado visto')
                                        ->live()
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function (bool $state, callable $set): void {
                                            if ($state) {
                                                $set('side_type', PlacementType::CostadoVisto->value);
                                                $set('_side_paredes', false);

                                                return;
                                            }

                                            $set('_side_paredes', true);
                                            $set('side_type', PlacementType::EntreParedes->value);
                                        }),
                                ]),
                            Hidden::make('side_type')
                                ->default(PlacementType::EntreParedes->value)
                                ->dehydrated(),
                            Section::make('Paredes')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => self::isParedes($get('side_type')))
                                ->columns(2)
                                ->schema([
                                    TextInput::make('configuration.left_side_discount_cm')
                                        ->label('Descuento lado izquierdo (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                    TextInput::make('configuration.right_side_discount_cm')
                                        ->label('Descuento lado derecho (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                    ...self::shelfAndAccessoryFields(),
                                ]),
                            Section::make('Costado visto')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => self::isCostadoVisto($get('side_type')))
                                ->columns(2)
                                ->schema([
                                    TextInput::make('configuration.side_a_discount_cm')
                                        ->label('Descuento lado A (pared) (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                    TextInput::make('configuration.side_b_discount_cm')
                                        ->label('Descuento lado B (costado visto) (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_SIDE_B_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                ]),
                            Section::make('Baldas y accesorios')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => self::isCostadoVisto($get('side_type')))
                                ->columns(4)
                                ->schema(self::shelfAndAccessoryFields()),
                            Section::make('Profundidad por balda (correderas)')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => self::isCorrederas($get('furniture_type')))
                                ->columns(4)
                                ->schema(self::correderasShelfDepthFields()),
                            Section::make('Laterales interiores')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => filled($get('furniture_type')))
                                ->columns(4)
                                ->schema(self::lateralInteriorFields()),
                            Section::make('Descuentos adicionales')
                                ->collapsible()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('configuration.rear_discount_cm')
                                        ->label('Descuento trasero (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                    TextInput::make('configuration.trunk_shelf_discount_cm')
                                        ->label('Descuento balda maletero (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->visible(fn (Get $get): bool => self::doorCount($get('furniture_type')) === 2),
                                ]),
                            Hidden::make('is_active')->default(true),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function shelfAndAccessoryFields(): array
    {
        return [
            TextInput::make('configuration.shelf_count')
                ->label('Número de baldas')
                ->numeric()
                ->default(2)
                ->minValue(0)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                    self::syncCorrederasShelfDepths($set, $get, (int) ($state ?? 0));
                }),
            TextInput::make('configuration.shelf_depth_cm')
                ->label('Profundidad de baldas (cm)')
                ->numeric()
                ->default(fn (Get $get): float => max(
                    0,
                    (float) ($get('opening_depth_cm') ?? 60) - FurniturePreset::DEFAULT_SHELF_DEPTH_OFFSET_CM,
                ))
                ->step(0.01)
                ->suffix('cm')
                ->visible(fn (Get $get): bool => ! self::isCorrederas($get('furniture_type'))),
            TextInput::make('configuration.interior_shelves_count')
                ->label('Baldas interiores')
                ->numeric()
                ->default(0)
                ->minValue(0),
            TextInput::make('configuration.interior_shelf_measure_cm')
                ->label('Medida balda interior (cm)')
                ->numeric()
                ->step(0.01)
                ->suffix('cm'),
            TextInput::make('configuration.drawers_count')
                ->label('Cajones')
                ->numeric()
                ->default(0)
                ->minValue(0),
            TextInput::make('configuration.drawer_height_cm')
                ->label('Medida cajón — alto (cm)')
                ->numeric()
                ->step(0.01)
                ->suffix('cm'),
            TextInput::make('configuration.drawer_width_cm')
                ->label('Medida cajón — ancho (cm)')
                ->numeric()
                ->step(0.01)
                ->suffix('cm'),
            TextInput::make('configuration.hanging_bars_count')
                ->label('Barras de colgar')
                ->numeric()
                ->default(0)
                ->minValue(0),
            TextInput::make('configuration.hanging_bar_measure_m')
                ->label('Medida barra de colgar (m)')
                ->numeric()
                ->step(0.01)
                ->suffix('m'),
        ];
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function correderasShelfDepthFields(): array
    {
        $fields = [];

        for ($index = 1; $index <= 12; $index++) {
            $fields[] = TextInput::make("configuration.shelf_depths_cm.{$index}")
                ->label("Profundidad balda {$index} (cm)")
                ->numeric()
                ->default(fn (Get $get): float => max(
                    0,
                    (float) ($get('opening_depth_cm') ?? 60) - FurniturePreset::DEFAULT_CORREDERAS_SHELF_OFFSET_CM,
                ))
                ->step(0.01)
                ->suffix('cm')
                ->visible(fn (Get $get): bool => self::isCorrederas($get('furniture_type'))
                    && $index <= (int) ($get('configuration.shelf_count') ?? 0));
        }

        return $fields;
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function lateralInteriorFields(): array
    {
        $fields = [];

        for ($index = 1; $index <= 6; $index++) {
            $fields[] = TextInput::make("configuration.lateral_interior_depths_cm.{$index}")
                ->label(self::lateralInteriorLabel($index).' (cm)')
                ->numeric()
                ->step(0.01)
                ->suffix('cm')
                ->visible(fn (Get $get): bool => $index <= self::lateralInteriorCount($get('furniture_type')));
        }

        return $fields;
    }

    protected static function lateralInteriorLabel(int $index): string
    {
        $names = [
            1 => 'uno',
            2 => 'dos',
            3 => 'tres',
            4 => 'cuatro',
            5 => 'cinco',
            6 => 'seis',
        ];

        return 'Lateral interior '.$names[$index];
    }

    protected static function syncShelfDepthDefaults(callable $set, Get $get, float $openingDepthCm): void
    {
        $set(
            'configuration.shelf_depth_cm',
            max(0, $openingDepthCm - FurniturePreset::DEFAULT_SHELF_DEPTH_OFFSET_CM),
        );

        self::syncCorrederasShelfDepths($set, $get, (int) ($get('configuration.shelf_count') ?? 0), $openingDepthCm);
    }

    protected static function syncCorrederasShelfDepths(
        callable $set,
        Get $get,
        int $shelfCount,
        ?float $openingDepthCm = null,
    ): void {
        $openingDepthCm ??= (float) ($get('opening_depth_cm') ?? 60);
        $defaultDepth = max(0, $openingDepthCm - FurniturePreset::DEFAULT_CORREDERAS_SHELF_OFFSET_CM);

        for ($index = 1; $index <= $shelfCount; $index++) {
            $current = $get("configuration.shelf_depths_cm.{$index}");

            if ($current === null || $current === '') {
                $set("configuration.shelf_depths_cm.{$index}", $defaultDepth);
            }
        }
    }

    protected static function isParedes(mixed $sideType): bool
    {
        return PlacementType::tryFrom((string) $sideType) === PlacementType::EntreParedes;
    }

    protected static function isCostadoVisto(mixed $sideType): bool
    {
        return PlacementType::tryFrom((string) $sideType) === PlacementType::CostadoVisto;
    }

    protected static function isCorrederas(mixed $furnitureType): bool
    {
        return FurnitureCabinetType::tryFromState(is_string($furnitureType) ? $furnitureType : null)?->isCorrederas() ?? false;
    }

    protected static function doorCount(mixed $furnitureType): int
    {
        return FurnitureCabinetType::tryFromState(is_string($furnitureType) ? $furnitureType : null)?->doorCount() ?? 0;
    }

    protected static function lateralInteriorCount(mixed $furnitureType): int
    {
        return FurnitureCabinetType::tryFromState(is_string($furnitureType) ? $furnitureType : null)?->lateralInteriorFieldCount() ?? 0;
    }
}
