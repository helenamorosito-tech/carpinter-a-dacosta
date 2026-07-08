<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FurniturePresetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('furniture_type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('opening_height_m')
                    ->label('Alto (m)')
                    ->sortable(),
                TextColumn::make('opening_width_m')
                    ->label('Ancho (m)')
                    ->sortable(),
                TextColumn::make('opening_depth_cm')
                    ->label('Profundidad (cm)')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
