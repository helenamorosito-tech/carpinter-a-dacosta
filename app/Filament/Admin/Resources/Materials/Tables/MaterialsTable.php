<?php

namespace App\Filament\Admin\Resources\Materials\Tables;

use App\Support\FilamentUiBadges;
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
                FilamentUiBadges::nameColumn(
                    TextColumn::make('name')
                        ->label('Nombre')
                        ->searchable()
                        ->sortable()
                ),
                FilamentUiBadges::typeColumn(
                    TextColumn::make('type')
                        ->label('Tipo')
                        ->sortable()
                ),
                FilamentUiBadges::widthColumn(
                    TextColumn::make('width_cm')
                        ->label('Ancho')
                        ->suffix(' cm')
                        ->sortable()
                ),
                FilamentUiBadges::heightColumn(
                    TextColumn::make('height_cm')
                        ->label('Alto')
                        ->suffix(' cm')
                        ->sortable()
                ),
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
                FilamentUiBadges::updatedColumn(
                    TextColumn::make('updated_at')
                        ->label('Actualizado')
                        ->dateTime('d/m/Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                ),
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
