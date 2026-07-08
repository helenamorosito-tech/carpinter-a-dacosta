<?php

namespace App\Filament\Admin\Resources\FurnitureProjects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FurnitureProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('preset.name')
                    ->label('Preset')
                    ->placeholder('—'),
                TextColumn::make('height_cm')
                    ->label('Alto')
                    ->suffix(' cm'),
                TextColumn::make('width_cm')
                    ->label('Ancho')
                    ->suffix(' cm'),
                TextColumn::make('depth_cm')
                    ->label('Fondo')
                    ->suffix(' cm'),
                TextColumn::make('door_count')
                    ->label('Puertas')
                    ->placeholder('—'),
                TextColumn::make('cut_plan.total_boards')
                    ->label('Tableros')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'calculated' => 'success',
                        'draft' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
