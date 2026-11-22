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
                Section::make('Tablero en stock')
                    ->columns(4)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(MaterialType::class)
                            ->default(MaterialType::Textil)
                            ->required()
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('width_cm')
                            ->label('Ancho')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(122)
                            ->suffix('cm'),
                        TextInput::make('height_cm')
                            ->label('Alto')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(244)
                            ->suffix('cm'),
                        TextInput::make('thickness_cm')
                            ->label('Grosor')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->default(1.6)
                            ->suffix('cm'),
                        TextInput::make('quantity')
                            ->label('Stock')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
