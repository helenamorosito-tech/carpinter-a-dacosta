<?php

namespace App\Filament\Admin\Resources\FurnitureProjects\Schemas;

use App\Enums\DoorType;
use App\Enums\FurnitureWorkStatus;
use App\Enums\PlacementType;
use App\Models\FurniturePreset;
use App\Models\Material;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class FurnitureProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Mueble')
                        ->description('Selecciona el preset y las medidas (alto → ancho → fondo)')
                        ->schema([
                            Section::make()->schema([
                                TextInput::make('name')
                                    ->label('Nombre del proyecto')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('reference')
                                    ->label('Referencia')
                                    ->maxLength(255),
                                TextInput::make('price')
                                    ->label('Precio')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->minValue(0),
                                Select::make('status')
                                    ->label('Estado')
                                    ->options(collect(FurnitureWorkStatus::cases())->mapWithKeys(
                                        fn (FurnitureWorkStatus $status): array => [$status->value => $status->getLabel()],
                                    )->all())
                                    ->default(FurnitureWorkStatus::EnProyecto->value)
                                    ->required()
                                    ->native(false),
                                Select::make('furniture_preset_id')
                                    ->label('Tipo de mueble (preset)')
                                    ->options(fn () => FurniturePreset::query()->where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($set, ?string $state): void {
                                        if (! $state) {
                                            return;
                                        }

                                        $preset = FurniturePreset::find($state);

                                        if (! $preset) {
                                            return;
                                        }

                                        $set('shelf_count', $preset->default_shelf_count);
                                        $set('shelf_thickness_cm', $preset->default_shelf_thickness_cm);
                                    }),
                                TextInput::make('height_cm')
                                    ->label('Alto (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->minValue(1),
                                TextInput::make('width_cm')
                                    ->label('Ancho (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->minValue(1),
                                TextInput::make('depth_cm')
                                    ->label('Fondo (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->minValue(1),
                            ])->columns(2),
                        ]),
                    Step::make('Configuración')
                        ->description('Ubicación, puertas, baldas y módulos')
                        ->schema([
                            Section::make()->schema([
                                Select::make('placement_type')
                                    ->label('Ubicación del mueble')
                                    ->options(PlacementType::class)
                                    ->default(PlacementType::CostadoVisto)
                                    ->required()
                                    ->native(false)
                                    ->helperText('Entre paredes descuenta 4,5 cm por cada lado del ancho.'),
                                Select::make('door_type')
                                    ->label('Tipo de puertas')
                                    ->options(DoorType::class)
                                    ->default(DoorType::Abatibles)
                                    ->required()
                                    ->native(false),
                                TextInput::make('shelf_count')
                                    ->label('Número de baldas (tramos)')
                                    ->numeric()
                                    ->default(2)
                                    ->minValue(0),
                                TextInput::make('shelf_thickness_cm')
                                    ->label('Grosor de lámina (cm)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(1.6)
                                    ->helperText('Se descuenta del largo de las baldas (2 × grosor).'),
                                TextInput::make('module_count')
                                    ->label('Módulos del mueble')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                            ])->columns(2),
                        ]),
                    Step::make('Material')
                        ->description('Tablero disponible en stock para el plan de corte')
                        ->schema([
                            Section::make()->schema([
                                Select::make('material_id')
                                    ->label('Material / tablero')
                                    ->options(fn () => Material::query()
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn (Material $material): array => [
                                            $material->id => "{$material->name} — {$material->dimensions_label} (stock: {$material->quantity})",
                                        ]))
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->helperText('Se usarán las medidas del tablero para optimizar cortes y generar el diagrama.'),
                            ]),
                        ]),
                    Step::make('Resumen')
                        ->description('Verifica los datos antes de calcular')
                        ->schema([
                            Section::make('Resumen del cálculo')
                                ->schema([
                                    TextInput::make('summary_dimensions')
                                        ->label('Medidas')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn (Get $get): string => sprintf(
                                            '%s × %s × %s cm (alto × ancho × fondo)',
                                            $get('height_cm') ?? '—',
                                            $get('width_cm') ?? '—',
                                            $get('depth_cm') ?? '—',
                                        )),
                                    TextInput::make('summary_doors')
                                        ->label('Puertas estimadas')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(function (Get $get): string {
                                            $width = (float) ($get('width_cm') ?? 0);
                                            $placement = $get('placement_type');

                                            if ($placement === PlacementType::EntreParedes->value || $placement === PlacementType::EntreParedes) {
                                                $width = max(0, $width - 9);
                                            }

                                            $doors = $width <= 100 ? 2 : ($width <= 180 ? 3 : 4);

                                            return "{$doors} puertas (según ancho efectivo)";
                                        }),
                                ])->columns(2),
                        ]),
                ])->columnSpanFull(),
            ]);
    }
}
