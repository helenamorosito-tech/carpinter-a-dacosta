<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Schemas;

use App\Enums\PlacementType;
use App\Filament\Admin\Resources\FurniturePresets\Concerns\HasCutSheetPdfActions;
use App\Models\FurniturePreset;
use App\Models\FurnitureType;
use App\Services\Carpentry\AntFurnitureRemnantMatcher;
use App\Services\Carpentry\PresetCutCalculator;
use App\Support\BoardMaterialOptions;
use App\Support\FurnitureTypeCatalog;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class FurniturePresetForm
{
    use HasCutSheetPdfActions;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Tamaño de Hueco')
                        ->schema([
                            Section::make('Medidas del hueco')
                                ->collapsible()
                                ->columns(3)
                                ->schema([
                                    TextInput::make('opening_height_m')
                                        ->label('Alto del hueco (m)')
                                        ->numeric()
                                        ->required()
                                        ->default(2.50)
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('m')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                            $set('global_height_cm', round(((float) ($state ?? 0)) * 100, 2));
                                            self::syncSuggestedBackBoardQuantity($get, $set);
                                        }),
                                    TextInput::make('opening_width_m')
                                        ->label('Ancho del hueco (m)')
                                        ->numeric()
                                        ->required()
                                        ->default(2.00)
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('m')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                            $set('global_width_cm', round(((float) ($state ?? 0)) * 100, 2));
                                            self::syncSuggestedBackBoardQuantity($get, $set);
                                        }),
                                    TextInput::make('opening_depth_cm')
                                        ->label('Profundidad del hueco (cm)')
                                        ->numeric()
                                        ->required()
                                        ->default(60)
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                            self::syncShelfDepthDefaults($set, $get, (float) ($state ?? 60));
                                            $set('global_depth_cm', (float) ($state ?? 60));
                                            self::syncSuggestedBackBoardQuantity($get, $set);
                                        }),
                                ]),
                        ]),
                    Step::make('Medida Global del mueble')
                        ->schema([
                            Section::make('Medida Global del mueble')
                                ->collapsible()
                                ->columns(3)
                                ->schema([
                                    TextInput::make('global_height_cm')
                                        ->label('Alto Global')
                                        ->numeric()
                                        ->required()
                                        ->default(250)
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set): void {
                                            self::syncInteriorLateralDefaults($get, $set);
                                            self::syncSuggestedBackBoardQuantity($get, $set);
                                        }),
                                    TextInput::make('global_width_cm')
                                        ->label('Ancho Global')
                                        ->numeric()
                                        ->required()
                                        ->default(200)
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncSuggestedBackBoardQuantity($get, $set)),
                                    TextInput::make('global_depth_cm')
                                        ->label('Profundidad Global')
                                        ->numeric()
                                        ->required()
                                        ->default(60)
                                        ->minValue(0.01)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set): void {
                                            $depth = (float) ($get('global_depth_cm') ?? 60);
                                            self::syncShelfDepthDefaults($set, $get, $depth);
                                            self::syncSuggestedBackBoardQuantity($get, $set);
                                        }),
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
                                ->dehydrated()
                                ->live(),
                        ]),
                    Step::make('Tipo de mueble')
                        ->schema([
                            Section::make('Tipo de mueble')
                                ->collapsible(false)
                                ->schema([
                                    CheckboxList::make('furniture_type')
                                        ->label('Elige el tipo de mueble')
                                        ->options(fn (): array => FurnitureTypeCatalog::options())
                                        ->columns(2)
                                        ->required()
                                        ->live()
                                        ->afterStateHydrated(function (CheckboxList $component, mixed $state): void {
                                            if (is_string($state) && filled($state)) {
                                                $component->state([$state]);

                                                return;
                                            }

                                            if (! is_array($state)) {
                                                $component->state([]);
                                            }
                                        })
                                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                                            if (! is_array($state) || $state === []) {
                                                return;
                                            }

                                            $selected = array_values(array_filter($state, fn (mixed $value): bool => filled($value)));
                                            if (count($selected) <= 1) {
                                                return;
                                            }

                                            // Solo se admite una opción: se conserva la última marcada.
                                            $set('furniture_type', [end($selected)]);
                                        })
                                        ->dehydrateStateUsing(function (mixed $state): ?string {
                                            if (is_array($state)) {
                                                $selected = array_values(array_filter($state, fn (mixed $value): bool => filled($value)));

                                                return isset($selected[0]) ? (string) $selected[0] : null;
                                            }

                                            return filled($state) ? (string) $state : null;
                                        })
                                        ->helperText('Marca solo un tipo. Si marcas otro, se desmarca el anterior.'),
                                    Actions::make([
                                        Action::make('addFurnitureType')
                                            ->label('Agregar tipo de mueble')
                                            ->icon('heroicon-o-plus')
                                            ->color('gray')
                                            ->modalHeading('Nuevo tipo de mueble')
                                            ->modalSubmitActionLabel('Agregar a la lista')
                                            ->form([
                                                TextInput::make('name')
                                                    ->label('Nombre del tipo')
                                                    ->required()
                                                    ->maxLength(120)
                                                    ->placeholder('Ej. Armario esquinero'),
                                                Radio::make('door_style')
                                                    ->label('Tipo de puertas')
                                                    ->options([
                                                        'abrir' => 'De abrir',
                                                        'correderas' => 'Correderas',
                                                    ])
                                                    ->default('abrir')
                                                    ->inline()
                                                    ->required(),
                                                Select::make('door_count')
                                                    ->label('Número de puertas')
                                                    ->options([
                                                        2 => '2 puertas',
                                                        3 => '3 puertas',
                                                        4 => '4 puertas',
                                                    ])
                                                    ->default(2)
                                                    ->required()
                                                    ->native(false),
                                            ])
                                            ->action(function (array $data, Set $set): void {
                                                $type = FurnitureType::createFromLabel(
                                                    (string) ($data['name'] ?? ''),
                                                    ($data['door_style'] ?? 'abrir') === 'correderas',
                                                    (int) ($data['door_count'] ?? 2),
                                                );

                                                $set('furniture_type', [$type->slug]);

                                                Notification::make()
                                                    ->title('Tipo de mueble agregado')
                                                    ->body('"'.$type->name.'" ya está disponible en la lista.')
                                                    ->success()
                                                    ->send();
                                            }),
                                    ]),
                                ]),
                        ]),
                    Step::make('Datos del mueble')
                        ->schema([
                            self::furnitureTypeBanner(),
                            Section::make('Paredes')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => self::isParedes($get('side_type')))
                                ->columns(3)
                                ->schema([
                                    TextInput::make('configuration.left_side_discount_cm')
                                        ->label('Descuento lado izquierdo (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncAdditionalShelfDefaults($get, $set)),
                                    TextInput::make('configuration.right_side_discount_cm')
                                        ->label('Descuento lado derecho (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncAdditionalShelfDefaults($get, $set)),
                                    TextInput::make('configuration.rear_discount_cm')
                                        ->label('Descuento trasero (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm'),
                                ]),
                            Section::make('BASE Y ALTURA')
                                ->collapsible()
                                ->columns(3)
                                ->schema([
                                    TextInput::make('configuration.module_count')
                                        ->label('Número de módulos')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncSuggestedBackBoardQuantity($get, $set)),
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
                            Section::make('Costado visto')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => self::isCostadoVisto($get('side_type')))
                                ->columns(2)
                                ->schema([
                                    Checkbox::make('configuration.costado_visto_izquierda')
                                        ->label('Costado visto izquierda')
                                        ->default(true)
                                        ->live()
                                        ->afterStateUpdated(function (bool $state, Set $set): void {
                                            if ($state) {
                                                $set('configuration.costado_visto_derecha', false);

                                                return;
                                            }

                                            $set('configuration.costado_visto_derecha', true);
                                        }),
                                    Checkbox::make('configuration.costado_visto_derecha')
                                        ->label('Costado visto derecha')
                                        ->default(false)
                                        ->live()
                                        ->afterStateUpdated(function (bool $state, Set $set): void {
                                            if ($state) {
                                                $set('configuration.costado_visto_izquierda', false);

                                                return;
                                            }

                                            $set('configuration.costado_visto_izquierda', true);
                                        }),
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
                            Section::make('Laterales interiores')
                                ->collapsible()
                                ->visible(fn (Get $get): bool => filled($get('furniture_type')))
                                ->description('Opcional. Agrega uno o más laterales; cada uno se incluye en el plan de corte y en el PDF. Alto por defecto: alto global − 13 cm.')
                                ->schema(self::lateralInteriorFields()),
                            Hidden::make('is_active')->default(true),
                        ]),
                    Step::make('BALDAS')
                        ->schema([
                            self::furnitureTypeBanner(withTotalShelves: true),
                            Section::make('Balda maletero')
                                ->collapsible()
                                ->columns(4)
                                ->description('Corte en tablero 16 mm. Por defecto: ancho global − 6,5 cm y profundidad global − 9 cm.')
                                ->schema([
                                    TextInput::make('configuration.trunk_shelf_discount_cm')
                                        ->label('Descuento balda maletero (cm)')
                                        ->numeric()
                                        ->default(FurniturePreset::DEFAULT_DISCOUNT_CM)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->visible(fn (Get $get): bool => self::doorCount($get('furniture_type')) === 2),
                                    TextInput::make('configuration.trunk_shelf_length_cm')
                                        ->label('Ancho de la balda maletero')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->helperText('Por defecto: ancho global − 6,5 cm.'),
                                    TextInput::make('configuration.trunk_shelf_depth_cm')
                                        ->label('Profundidad de la balda maletero')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->helperText('Por defecto: profundidad global − 9 cm.'),
                                    Select::make('configuration.trunk_shelf_board_mm')
                                        ->label('Tablero para balda maletero')
                                        ->options([
                                            10 => '10 mm',
                                            16 => '16 mm',
                                            19 => '19 mm',
                                        ])
                                        ->default(16)
                                        ->native(false)
                                        ->required()
                                        ->live()
                                        ->helperText('Por defecto 16 mm.'),
                                ]),
                            Section::make('Baldas adicionales')
                                ->collapsible()
                                ->description('Opcional. Ancho por defecto: ancho global − 13 cm. Profundidad: profundidad global − 9 cm. Cada balda se detalla por separado.')
                                ->schema([
                                    Repeater::make('configuration.additional_shelves')
                                        ->label('Listado de baldas adicionales')
                                        ->defaultItems(0)
                                        ->addActionLabel('Agregar balda adicional')
                                        ->reorderable()
                                        ->collapsible()
                                        ->cloneable()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set): void {
                                            self::syncAdditionalShelfDefaults($get, $set);
                                            $set('configuration.shelf_count', self::totalShelvesCount($get));
                                        })
                                        ->itemLabel(function (array $state): string {
                                            $width = $state['width_cm'] ?? null;
                                            $depth = $state['depth_cm'] ?? null;
                                            $board = $state['board_mm'] ?? 16;

                                            if ($width === null && $depth === null) {
                                                return 'Balda adicional';
                                            }

                                            return sprintf(
                                                '%s × %s cm (%s mm)',
                                                $width !== null && $width !== '' ? measure_format((float) $width) : '—',
                                                $depth !== null && $depth !== '' ? measure_format((float) $depth) : '—',
                                                $board,
                                            );
                                        })
                                        ->columns(3)
                                        ->schema([
                                            TextInput::make('width_cm')
                                                ->label('Ancho')
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->suffix('cm')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->default(function (Get $get): ?float {
                                                    $width = (float) ($get('global_width_cm', isAbsolute: true) ?? 0);

                                                    return $width > 0
                                                        ? FurniturePreset::defaultAdditionalShelfWidthCm($width)
                                                        : null;
                                                })
                                                ->helperText('Por defecto: ancho global − 13 cm.'),
                                            TextInput::make('depth_cm')
                                                ->label('Profundidad')
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->suffix('cm')
                                                ->required()
                                                ->live(onBlur: true)
                                                ->default(function (Get $get): ?float {
                                                    $depth = (float) ($get('global_depth_cm', isAbsolute: true) ?? 0);

                                                    return $depth > 0
                                                        ? FurniturePreset::defaultAdditionalShelfDepthCm($depth)
                                                        : null;
                                                })
                                                ->helperText('Por defecto: profundidad global − 9 cm.'),
                                            Select::make('board_mm')
                                                ->label('Tablero')
                                                ->options([
                                                    10 => '10 mm',
                                                    16 => '16 mm',
                                                    19 => '19 mm',
                                                ])
                                                ->default(16)
                                                ->native(false)
                                                ->required()
                                                ->live(),
                                        ]),
                                ]),
                            Html::make(function (Get $get): HtmlString {
                                $total = self::totalShelvesCount($get);

                                return new HtmlString(
                                    '<div style="margin-top:0.75rem;display:flex;align-items:baseline;gap:0.5rem;">'
                                    .'<span style="font-size:22px;font-weight:700;">Total baldas:</span>'
                                    .'<span style="font-size:22px;font-weight:700;color:#dc2626;'
                                    .'animation:shelves-blink 1s step-start infinite;">'
                                    .e((string) $total)
                                    .'</span></div>'
                                    .'<style>@keyframes shelves-blink{0%,49%{opacity:1}50%,100%{opacity:0}}</style>'
                                );
                            }),
                            Hidden::make('configuration.shelf_count')
                                ->default(1)
                                ->dehydrated(),
                        ]),
                    Step::make('Cajones y Barra Colgar')
                        ->schema([
                            self::furnitureTypeBanner(),
                            Section::make('Cajones y Barra Colgar')
                                ->collapsible()
                                ->columns(3)
                                ->schema([
                                    TextInput::make('configuration.drawers_count')
                                        ->label('Cajones')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->live(onBlur: true),
                                    TextInput::make('configuration.drawer_width_cm')
                                        ->label('Medida cajón — ancho (cm)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->suffix('cm'),
                                    TextInput::make('configuration.drawer_height_cm')
                                        ->label('Medida cajón — alto (cm)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->suffix('cm'),
                                    TextInput::make('configuration.drawer_depth_cm')
                                        ->label('Medida cajón — profundidad (cm)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->default(fn (Get $get): ?float => filled($get('configuration.shelf_depth_cm'))
                                            ? (float) $get('configuration.shelf_depth_cm')
                                            : (filled($get('global_depth_cm'))
                                                ? max(0, (float) $get('global_depth_cm') - FurniturePreset::DEFAULT_SHELF_DEPTH_OFFSET_CM)
                                                : null)),
                                    TextInput::make('configuration.hanging_bars_count')
                                        ->label('Número de barras de colgar')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->live(onBlur: true),
                                    TextInput::make('configuration.hanging_bar_length_cm')
                                        ->label('Medida barra de colgar (cm)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->minValue(0.01)
                                        ->suffix('cm')
                                        ->required(fn (Get $get): bool => (int) ($get('configuration.hanging_bars_count') ?? 0) >= 1)
                                        ->helperText(fn (Get $get): ?string => (int) ($get('configuration.hanging_bars_count') ?? 0) >= 1
                                            ? 'Obligatoria si hay al menos 1 barra de colgar.'
                                            : null),
                                    TextInput::make('configuration.shelf_depth_cm')
                                        ->label('Profundidad de baldas (cm)')
                                        ->numeric()
                                        ->default(fn (Get $get): float => max(
                                            0,
                                            (float) ($get('global_depth_cm') ?? $get('opening_depth_cm') ?? 60)
                                                - FurniturePreset::DEFAULT_SHELF_DEPTH_OFFSET_CM,
                                        ))
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->visible(fn (Get $get): bool => ! self::isCorrederas($get('furniture_type'))),
                                ]),
                        ]),
                    Step::make('Material y cortes')
                        ->schema([
                            self::furnitureTypeBanner(),
                            Section::make('Tamaño de tablero')
                                ->collapsible()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('board_width_cm')
                                        ->label('Ancho del tablero')
                                        ->numeric()
                                        ->required()
                                        ->default(FurniturePreset::DEFAULT_BOARD_WIDTH_CM)
                                        ->minValue(1)
                                        ->maxValue(FurniturePreset::DEFAULT_BOARD_WIDTH_CM)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncSuggestedBackBoardQuantity($get, $set)),
                                    TextInput::make('board_height_cm')
                                        ->label('Alto del tablero')
                                        ->numeric()
                                        ->required()
                                        ->default(FurniturePreset::DEFAULT_BOARD_HEIGHT_CM)
                                        ->minValue(1)
                                        ->maxValue(FurniturePreset::DEFAULT_BOARD_HEIGHT_CM)
                                        ->step(0.01)
                                        ->suffix('cm')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncSuggestedBackBoardQuantity($get, $set))
                                        ->helperText('Máximo 122 × 244 cm (1,22 × 2,44 m). Los cortes no pueden superar este tamaño.'),
                                ]),
                            Section::make('Listado de tableros')
                                ->collapsible()
                                ->schema([
                                    Select::make('structure_board_thickness_mm')
                                        ->label('Grosor tableros de estructura')
                                        ->options([
                                            10 => '10 mm',
                                            16 => '16 mm',
                                            19 => '19 mm',
                                        ])
                                        ->default(16)
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function (mixed $state, Get $get, Set $set): void {
                                            $thicknessMm = (int) ($state ?? 16);
                                            $current = $get('selected_boards');
                                            $keys = is_array($current)
                                                ? $current
                                                : (filled($current) ? [(string) $current] : []);
                                            $remapped = BoardMaterialOptions::remappedToThickness($keys, $thicknessMm);
                                            $set('selected_boards', $remapped[0] ?? 'blanco_'.$thicknessMm);
                                        })
                                        ->helperText('Por defecto 16 mm (editable). Aplica al techo, base y laterales del exterior.'),
                                    Radio::make('selected_boards')
                                        ->label('Tipo de material / color')
                                        ->options(fn (Get $get): array => BoardMaterialOptions::optionsForThickness(
                                            (int) ($get('structure_board_thickness_mm') ?? 16),
                                        ))
                                        ->default(BoardMaterialOptions::defaultSelected()[0] ?? 'blanco_16')
                                        ->required()
                                        ->columns(2)
                                        ->live()
                                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                                            if (! filled($state) || is_array($state)) {
                                                return;
                                            }

                                            $set(
                                                'structure_board_thickness_mm',
                                                BoardMaterialOptions::primaryThicknessMm([(string) $state]),
                                            );
                                        })
                                        ->helperText(new HtmlString(
                                            '<span class="text-gray-600 dark:text-gray-300">Solo una opción. Se muestran los materiales del grosor de estructura elegido.</span>'
                                        )),
                                ]),
                            Section::make('Tableros de fondo del mueble')
                                ->collapsible()
                                ->description('Por defecto: 1 fondo en 1 tablero de 10 mm (ancho × alto globales, un solo corte). Editable si hace falta.')
                                ->columns(2)
                                ->schema([
                                    Select::make('back_board_thickness_mm')
                                        ->label('Grosor tablero de fondo')
                                        ->options([
                                            10 => '10 mm',
                                            16 => '16 mm',
                                            19 => '19 mm',
                                        ])
                                        ->default(FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM)
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->helperText('Por defecto 10 mm. Editable si se necesita otro grosor.'),
                                    TextInput::make('back_board_quantity')
                                        ->label('Cantidad de fondos')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(1)
                                        ->default(FurniturePreset::DEFAULT_BACK_BOARD_QUANTITY)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->helperText('Por defecto 1 fondo por mueble (1 corte en 1 tablero).'),
                                ]),
                            Section::make('Cálculo de cortes')
                                ->collapsible()
                                ->description('Plan de cortes, aprovechamiento de sobrantes del almacén (S-XX) y tableros nuevos necesarios.')
                                ->schema([
                                    View::make('filament.presets.cut-preview')
                                        ->viewData(function (Get $get): array {
                                            $result = app(PresetCutCalculator::class)->calculate([
                                                'global_height_cm' => $get('global_height_cm'),
                                                'global_width_cm' => $get('global_width_cm'),
                                                'global_depth_cm' => $get('global_depth_cm'),
                                                'side_type' => $get('side_type'),
                                                'configuration' => [
                                                    'shelf_count' => $get('configuration.shelf_count'),
                                                    'module_count' => $get('configuration.module_count'),
                                                    'trunk_shelf_length_cm' => $get('configuration.trunk_shelf_length_cm'),
                                                    'trunk_shelf_depth_cm' => $get('configuration.trunk_shelf_depth_cm'),
                                                    'trunk_shelf_board_mm' => $get('configuration.trunk_shelf_board_mm'),
                                                    'additional_shelves' => $get('configuration.additional_shelves') ?? [],
                                                    'interior_laterals' => $get('configuration.interior_laterals') ?? [],
                                                ],
                                                'board_width_cm' => $get('board_width_cm'),
                                                'board_height_cm' => $get('board_height_cm'),
                                                'selected_boards' => is_array($get('selected_boards'))
                                                    ? $get('selected_boards')
                                                    : (filled($get('selected_boards')) ? [(string) $get('selected_boards')] : []),
                                                'structure_board_thickness_mm' => $get('structure_board_thickness_mm') ?? 16,
                                                'back_board_thickness_mm' => $get('back_board_thickness_mm') ?? FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM,
                                                'back_board_quantity' => $get('back_board_quantity'),
                                            ]);

                                            $antMatches = app(AntFurnitureRemnantMatcher::class)->matchForPieces(
                                                $result['pieces'] ?? [],
                                                (int) ($result['structure_board_thickness_mm'] ?? 16),
                                            );

                                            return [
                                                'result' => $result,
                                                'antMatches' => $antMatches,
                                            ];
                                        }),
                                ]),
                            Section::make('Plan de corte PDF')
                                ->collapsible()
                                ->description('Previsualiza o descarga el listado de tableros, piezas, sobrantes y dibujos de cortes. Incluye los mismos sobrantes S-XX mostrados arriba.')
                                ->schema([
                                    Actions::make(self::cutSheetPdfActions()),
                                ]),
                        ]),
                ])
                    ->extraAlpineAttributes([
                        // Sin scroll en encabezados del wizard al avanzar; solo vuelve arriba de la página.
                        'x-init' => <<<'JS'
                            const scrollPageToTop = () => {
                                window.scrollTo(0, 0)
                                document.documentElement.scrollTop = 0
                                document.body.scrollTop = 0

                                for (const selector of ['.fi-main-ctn', '.fi-main', '.fi-body', '.fi-layout', '.fi-page']) {
                                    const el = document.querySelector(selector)
                                    if (! el) continue
                                    el.scrollTop = 0
                                    if (typeof el.scrollTo === 'function') {
                                        el.scrollTo(0, 0)
                                    }
                                }
                            }

                            this.scroll = () => {
                                scrollPageToTop()
                                this.$nextTick(scrollPageToTop)
                                setTimeout(scrollPageToTop, 0)
                                setTimeout(scrollPageToTop, 50)
                            }

                            this.autofocusFields = () => {}

                            this.$watch('step', () => this.scroll())
                        JS,
                    ])
                    ->extraAttributes([
                        'class' => 'fi-preset-wizard',
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected static function lateralInteriorFields(): array
    {
        return [
            Repeater::make('configuration.interior_laterals')
                ->label('Listado de laterales interiores')
                ->defaultItems(0)
                ->addActionLabel('Agregar lateral interior')
                ->reorderable()
                ->collapsible()
                ->cloneable()
                ->itemLabel(function (array $state): string {
                    $height = $state['height_cm'] ?? null;
                    $board = $state['board_mm'] ?? ($state['board_choice'] ?? 16);

                    if ($height === null || $height === '') {
                        return 'Lateral interior';
                    }

                    return sprintf(
                        'Alto %s cm (%s mm)',
                        measure_format((float) $height),
                        $board === 'other' ? ($state['board_mm'] ?? '—') : $board,
                    );
                })
                ->columns(3)
                ->schema([
                    TextInput::make('height_cm')
                        ->label('Alto del lateral')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->suffix('cm')
                        ->required()
                        ->live(onBlur: true)
                        ->default(fn (Get $get): ?float => self::defaultInteriorLateralHeight($get))
                        ->helperText('Por defecto: alto global − 13 cm.'),
                    Select::make('board_choice')
                        ->label('Tablero (Grosor)')
                        ->options([
                            '16' => '16 mm',
                            '1' => '1 mm',
                            'other' => 'Otro grosor…',
                        ])
                        ->default('16')
                        ->native(false)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            if ($state === '16' || $state === '1') {
                                $set('board_mm', (int) $state);
                            }
                        }),
                    TextInput::make('board_mm')
                        ->label('Grosor personalizado')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->suffix('mm')
                        ->default(16)
                        ->required(fn (Get $get): bool => ($get('board_choice') ?? '16') === 'other')
                        ->visible(fn (Get $get): bool => ($get('board_choice') ?? '16') === 'other'),
                ]),
        ];
    }

    protected static function defaultInteriorLateralHeight(Get $get): ?float
    {
        $height = (float) ($get('global_height_cm', isAbsolute: true) ?? $get('global_height_cm') ?? 0);

        if ($height <= 0) {
            return null;
        }

        return round(max(0, $height - FurniturePreset::DEFAULT_INTERIOR_LATERAL_HEIGHT_OFFSET_CM), 2);
    }

    protected static function syncInteriorLateralDefaults(Get $get, Set $set): void
    {
        $laterals = $get('configuration.interior_laterals');
        if (! is_array($laterals) || $laterals === []) {
            return;
        }

        $defaultHeight = self::defaultInteriorLateralHeight($get);

        foreach ($laterals as $index => $lateral) {
            if (! is_array($lateral)) {
                continue;
            }

            if (blank($lateral['height_cm'] ?? null) && $defaultHeight !== null) {
                $laterals[$index]['height_cm'] = $defaultHeight;
            }

            if (blank($lateral['board_choice'] ?? null)) {
                $laterals[$index]['board_choice'] = '16';
                $laterals[$index]['board_mm'] = 16;
            }
        }

        $set('configuration.interior_laterals', $laterals);
    }

    protected static function syncShelfDepthDefaults(callable $set, Get $get, float $depthCm): void
    {
        $set(
            'configuration.shelf_depth_cm',
            round(max(0, $depthCm - FurniturePreset::DEFAULT_SHELF_DEPTH_OFFSET_CM), 2),
        );

        self::syncCorrederasShelfDepths($set, $get, (int) ($get('configuration.shelf_count') ?? 0), $depthCm);
    }

    protected static function syncCorrederasShelfDepths(
        callable $set,
        Get $get,
        int $shelfCount,
        ?float $depthCm = null,
    ): void {
        $depthCm ??= (float) ($get('global_depth_cm') ?? $get('opening_depth_cm') ?? 60);
        $defaultDepth = round(max(0, $depthCm - FurniturePreset::DEFAULT_CORREDERAS_SHELF_OFFSET_CM), 2);

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
        return FurnitureTypeCatalog::isCorrederas($furnitureType);
    }

    protected static function syncSuggestedBackBoardQuantity(Get $get, Set $set): void
    {
        if (blank($get('back_board_quantity')) || (int) $get('back_board_quantity') < 1) {
            $set('back_board_quantity', FurniturePreset::DEFAULT_BACK_BOARD_QUANTITY);
        }

        if (blank($get('back_board_thickness_mm'))) {
            $set('back_board_thickness_mm', FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM);
        }

        self::syncTrunkShelfDefaults($get, $set);
    }

    protected static function syncTrunkShelfDefaults(Get $get, Set $set): void
    {
        $width = (float) ($get('global_width_cm') ?? 0);
        $depth = (float) ($get('global_depth_cm') ?? 0);

        if ($width > 0) {
            $set(
                'configuration.trunk_shelf_length_cm',
                round(max(0, $width - FurniturePreset::DEFAULT_TRUNK_SHELF_WIDTH_DISCOUNT_CM), 2),
            );
        }

        if ($depth > 0) {
            $set(
                'configuration.trunk_shelf_depth_cm',
                round(max(0, $depth - FurniturePreset::DEFAULT_TRUNK_SHELF_DEPTH_OFFSET_CM), 2),
            );
        }

        self::syncAdditionalShelfDefaults($get, $set);

        if (blank($get('configuration.trunk_shelf_board_mm'))) {
            $set('configuration.trunk_shelf_board_mm', 16);
        }
    }

    protected static function syncAdditionalShelfDefaults(Get $get, Set $set): void
    {
        $shelves = $get('configuration.additional_shelves');
        if (! is_array($shelves) || $shelves === []) {
            return;
        }

        $width = (float) ($get('global_width_cm') ?? 0);
        $depth = (float) ($get('global_depth_cm') ?? 0);
        $defaultWidth = FurniturePreset::defaultAdditionalShelfWidthCm($width);
        $defaultDepth = FurniturePreset::defaultAdditionalShelfDepthCm($depth);

        foreach ($shelves as $index => $shelf) {
            if (! is_array($shelf)) {
                continue;
            }
            if (blank($shelf['width_cm'] ?? null) && $defaultWidth > 0) {
                $shelves[$index]['width_cm'] = $defaultWidth;
            }
            if (blank($shelf['depth_cm'] ?? null) && $defaultDepth > 0) {
                $shelves[$index]['depth_cm'] = $defaultDepth;
            }
            if (blank($shelf['board_mm'] ?? null)) {
                $shelves[$index]['board_mm'] = 16;
            }
        }

        $set('configuration.additional_shelves', $shelves);
    }

    protected static function doorCount(mixed $furnitureType): int
    {
        return FurnitureTypeCatalog::doorCount($furnitureType);
    }

    protected static function furnitureTypeBanner(bool $withTotalShelves = false): View
    {
        return View::make('filament.presets.furniture-type-banner')
            ->viewData(fn (Get $get): array => [
                'furnitureTypeLabel' => FurnitureTypeCatalog::label($get('furniture_type')),
                'totalShelves' => $withTotalShelves ? self::totalShelvesCount($get) : null,
                'showTotalShelves' => $withTotalShelves,
            ])
            ->key(fn (Get $get): string => $withTotalShelves
                ? 'furniture-type-banner-shelves-'.self::totalShelvesCount($get)
                : 'furniture-type-banner')
            ->visible(fn (Get $get): bool => filled(FurnitureTypeCatalog::label($get('furniture_type'))));
    }

    protected static function totalShelvesCount(Get $get): int
    {
        $additional = $get('configuration.additional_shelves');
        $additionalCount = is_array($additional) ? count($additional) : 0;

        // Siempre incluye la balda maletero.
        return 1 + $additionalCount;
    }
}
