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
use Filament\Schemas\Schema;

class FurnitureProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Obra')
                    ->columns(6)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(255)
                            ->columnSpan(2),
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
                            ->label('Mueble de partida')
                            ->options(fn () => FurniturePreset::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->columnSpan(3)
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
                            ->label('Alto')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->minValue(1)
                            ->suffix('cm'),
                        TextInput::make('width_cm')
                            ->label('Ancho')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->minValue(1)
                            ->suffix('cm'),
                        TextInput::make('depth_cm')
                            ->label('Fondo')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->minValue(1)
                            ->suffix('cm'),
                    ]),
                Section::make('Cuerpo')
                    ->columns(5)
                    ->schema([
                        Select::make('placement_type')
                            ->label('Apoyo')
                            ->options(PlacementType::class)
                            ->default(PlacementType::CostadoVisto)
                            ->required()
                            ->native(false),
                        Select::make('door_type')
                            ->label('Puertas')
                            ->options(DoorType::class)
                            ->default(DoorType::Abatibles)
                            ->required()
                            ->native(false),
                        TextInput::make('module_count')
                            ->label('Módulos')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        TextInput::make('shelf_count')
                            ->label('Baldas')
                            ->numeric()
                            ->default(2)
                            ->minValue(0),
                        TextInput::make('shelf_thickness_cm')
                            ->label('Grosor lámina')
                            ->numeric()
                            ->step(0.01)
                            ->default(1.6)
                            ->suffix('cm'),
                    ]),
                Section::make('Corte')
                    ->columns(2)
                    ->schema([
                        Select::make('material_id')
                            ->label('Tablero')
                            ->options(fn () => Material::query()
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn (Material $material): array => [
                                    $material->id => "{$material->name} — {$material->dimensions_label} (stock: {$material->quantity})",
                                ]))
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->columnSpan(2),
                        TextInput::make('summary_dimensions')
                            ->label('Medidas')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (Get $get): string => sprintf(
                                '%s × %s × %s cm',
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

                                return "{$doors} puertas";
                            }),
                    ]),
            ]);
    }
}
