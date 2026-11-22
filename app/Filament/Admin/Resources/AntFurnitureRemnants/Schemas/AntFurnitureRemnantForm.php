<?php

namespace App\Filament\Admin\Resources\AntFurnitureRemnants\Schemas;

use App\Enums\AntRemnantOrigin;
use App\Enums\BoardColor;
use App\Enums\BoardThickness;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AntFurnitureRemnantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sobrante')
                    ->description('El ID S-XX se asigna al guardar.')
                    ->columns(4)
                    ->schema([
                        TextInput::make('code')
                            ->label('ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('S-XX')
                            ->visibleOn('edit'),
                        TextInput::make('name')
                            ->label('Pieza')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(fn (string $operation): int => $operation === 'edit' ? 2 : 3),
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'available' => 'Disponible',
                                'reserved' => 'Reservada',
                                'used' => 'Usada',
                            ])
                            ->required()
                            ->native(false)
                            ->default('available'),
                        TextInput::make('width_cm')
                            ->label('Ancho')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->suffix('cm'),
                        TextInput::make('length_cm')
                            ->label('Largo')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->suffix('cm'),
                        Select::make('thickness_mm')
                            ->label('Grosor')
                            ->options(collect(BoardThickness::cases())->mapWithKeys(
                                fn (BoardThickness $t): array => [$t->value => $t->getLabel()],
                            )->all())
                            ->required()
                            ->native(false)
                            ->default(BoardThickness::Mm16->value),
                        Select::make('color')
                            ->label('Tablero')
                            ->options(collect(BoardColor::cases())->mapWithKeys(
                                fn (BoardColor $c): array => [$c->value => $c->getLabel()],
                            )->all())
                            ->required()
                            ->native(false)
                            ->default(BoardColor::Blanco->value),
                        Select::make('origin')
                            ->label('Origen')
                            ->options([
                                AntRemnantOrigin::Registered->value => AntRemnantOrigin::Registered->getLabel(),
                                AntRemnantOrigin::SystemSobrante->value => AntRemnantOrigin::SystemSobrante->getLabel(),
                            ])
                            ->required()
                            ->native(false)
                            ->default(AntRemnantOrigin::Registered->value)
                            ->columnSpan(2),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
