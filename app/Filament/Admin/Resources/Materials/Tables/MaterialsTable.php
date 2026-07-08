<?php

namespace App\Filament\Admin\Resources\Materials\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('width_cm')
                    ->label('Ancho')
                    ->suffix(' cm')
                    ->sortable(),
                TextColumn::make('height_cm')
                    ->label('Alto')
                    ->suffix(' cm')
                    ->sortable(),
                TextColumn::make('thickness_cm')
                    ->label('Grosor')
                    ->suffix(' cm')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
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
