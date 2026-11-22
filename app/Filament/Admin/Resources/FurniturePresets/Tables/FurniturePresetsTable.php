<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Tables;

use App\Models\FurniturePreset;
use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use App\Services\Carpentry\TechnicalSheetPdfGenerator;
use App\Support\FilamentUiBadges;
use App\Support\FurnitureTypeCatalog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Component;

class FurniturePresetsTable
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
                    TextColumn::make('furniture_type')
                        ->label('Tipo')
                        ->formatStateUsing(fn (?string $state): string => FurnitureTypeCatalog::label($state) ?? '—')
                        ->sortable()
                ),
                FilamentUiBadges::heightColumn(
                    TextColumn::make('global_height_cm')
                        ->label('Alto global')
                        ->suffix(' cm')
                        ->sortable()
                ),
                FilamentUiBadges::widthColumn(
                    TextColumn::make('global_width_cm')
                        ->label('Ancho global')
                        ->suffix(' cm')
                        ->sortable()
                ),
                TextColumn::make('calculated_boards_count')
                    ->label('Tableros')
                    ->sortable()
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                FilamentUiBadges::updatedColumn(
                    TextColumn::make('updated_at')
                        ->label('Actualizado')
                        ->dateTime('d/m/Y')
                        ->sortable()
                        ->toggleable()
                ),
                TextColumn::make('ver_pdf')
                    ->label('PDF')
                    ->state('VerPdf')
                    ->badge()
                    ->color(Color::Teal)
                    ->action(function (FurniturePreset $record, Component $livewire): void {
                        $generator = app(PresetCutSheetPdfGenerator::class);
                        $token = $generator->storePreview($record);
                        $url = $generator->previewUrl($token);

                        $livewire->js('window.open('.json_encode($url).', "_blank")');
                    }),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                Action::make('technicalSheet')
                    ->label('Ficha técnica')
                    ->icon('heroicon-o-document-text')
                    ->action(function (FurniturePreset $record) {
                        $generator = app(TechnicalSheetPdfGenerator::class);
                        $token = $generator->storePreview($record);

                        return redirect()->away($generator->previewUrl($token));
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
