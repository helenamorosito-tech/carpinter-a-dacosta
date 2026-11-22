<?php

namespace App\Filament\Admin\Resources\FurnitureProjects\Tables;

use App\Enums\FurnitureWorkStatus;
use App\Support\FilamentUiBadges;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FurnitureProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                FilamentUiBadges::nameColumn(
                    TextColumn::make('name')
                        ->label('Nombre del mueble')
                        ->searchable()
                        ->sortable()
                ),
                TextColumn::make('preset.name')
                    ->label('Ejemplo / preset')
                    ->placeholder('—')
                    ->toggleable(),
                FilamentUiBadges::priceColumn(
                    TextColumn::make('price')
                        ->label('Precio')
                        ->money('EUR', locale: 'es')
                        ->placeholder('—')
                        ->sortable()
                ),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof FurnitureWorkStatus
                        ? $state->getLabel()
                        : FurnitureWorkStatus::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                    ->color(fn ($state): string => match (true) {
                        $state instanceof FurnitureWorkStatus => $state->getColor() ?? 'gray',
                        default => FurnitureWorkStatus::tryFrom((string) $state)?->getColor() ?? 'gray',
                    }),
                FilamentUiBadges::heightColumn(
                    TextColumn::make('height_cm')
                        ->label('Alto')
                        ->suffix(' cm')
                        ->toggleable()
                ),
                FilamentUiBadges::widthColumn(
                    TextColumn::make('width_cm')
                        ->label('Ancho')
                        ->suffix(' cm')
                        ->toggleable()
                ),
                TextColumn::make('depth_cm')
                    ->label('Fondo')
                    ->suffix(' cm')
                    ->toggleable(isToggledHiddenByDefault: true),
                FilamentUiBadges::updatedColumn(
                    TextColumn::make('updated_at')
                        ->label('Actualizado')
                        ->dateTime('d/m/Y H:i')
                        ->sortable()
                ),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(FurnitureWorkStatus::cases())->mapWithKeys(
                        fn (FurnitureWorkStatus $status): array => [$status->value => $status->getLabel()],
                    )->all()),
            ])
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
