<?php

namespace App\Filament\Admin\Resources\Materials\Schemas;

use App\Enums\MaterialType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Material en stock')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Tipo de material')
                            ->options(MaterialType::class)
                            ->default(MaterialType::Textil)
                            ->required()
                            ->native(false),
                        TextInput::make('width_cm')
                            ->label('Ancho tablero (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(122)
                            ->suffix('cm'),
                        TextInput::make('height_cm')
                            ->label('Alto tablero (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(244)
                            ->suffix('cm'),
                        TextInput::make('thickness_cm')
                            ->label('Grosor lámina (cm)')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(1.6)
                            ->suffix('cm'),
                        TextInput::make('quantity')
                            ->label('Cantidad en stock')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
