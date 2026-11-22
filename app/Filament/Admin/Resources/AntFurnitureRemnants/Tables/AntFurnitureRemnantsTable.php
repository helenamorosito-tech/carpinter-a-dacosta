<?php

namespace App\Filament\Admin\Resources\AntFurnitureRemnants\Tables;

use App\Enums\AntRemnantOrigin;
use App\Enums\BoardColor;
use App\Enums\BoardThickness;
use App\Support\FilamentUiBadges;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AntFurnitureRemnantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                FilamentUiBadges::nameColumn(
                    TextColumn::make('name')
                        ->label('Nombre')
                        ->searchable()
                        ->sortable()
                ),
                FilamentUiBadges::typeColumn(
                    TextColumn::make('color')
                        ->label('Tipo')
                        ->formatStateUsing(fn ($state): string => $state instanceof BoardColor
                            ? $state->getLabel()
                            : (string) $state)
                        ->sortable()
                ),
                FilamentUiBadges::widthColumn(
                    TextColumn::make('width_cm')
                        ->label('Ancho')
                        ->suffix(' cm')
                        ->sortable()
                ),
                TextColumn::make('length_cm')
                    ->label('Largo')
                    ->suffix(' cm')
                    ->sortable(),
                TextColumn::make('thickness_mm')
                    ->label('Grosor')
                    ->suffix(' mm')
                    ->sortable(),
                TextColumn::make('origin')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AntRemnantOrigin
                        ? $state->getLabel()
                        : AntRemnantOrigin::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                    ->color(fn ($state): string => match (true) {
                        $state === AntRemnantOrigin::Registered,
                        $state === AntRemnantOrigin::Registered->value => 'info',
                        $state === AntRemnantOrigin::SystemSobrante,
                        $state === AntRemnantOrigin::SystemSobrante->value => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'reserved' => 'Reservada',
                        'used' => 'Usada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'used' => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                FilamentUiBadges::updatedColumn(
                    TextColumn::make('updated_at')
                        ->label('Actualizado')
                        ->dateTime('d/m/Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                ),
            ])
            ->filters([
                Filter::make('measures')
                    ->label('Buscar por medida')
                    ->columns(3)
                    ->schema([
                        TextInput::make('width_cm')
                            ->label('Ancho máx.')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->suffix('cm'),
                        TextInput::make('length_cm')
                            ->label('Largo máx.')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->suffix('cm'),
                        Select::make('thickness_mm')
                            ->label('Grosor')
                            ->options(collect(BoardThickness::cases())->mapWithKeys(
                                fn (BoardThickness $t): array => [$t->value => $t->getLabel()],
                            )->all())
                            ->native(false)
                            ->placeholder('Cualquiera'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $width = isset($data['width_cm']) && $data['width_cm'] !== '' && $data['width_cm'] !== null
                            ? (float) $data['width_cm']
                            : null;
                        $length = isset($data['length_cm']) && $data['length_cm'] !== '' && $data['length_cm'] !== null
                            ? (float) $data['length_cm']
                            : null;
                        $thickness = isset($data['thickness_mm']) && $data['thickness_mm'] !== '' && $data['thickness_mm'] !== null
                            ? (int) $data['thickness_mm']
                            : null;

                        if ($width === null && $length === null && $thickness === null) {
                            return $query;
                        }

                        // Grosor exacto: sin el mismo mm la pieza no sirve.
                        if ($thickness !== null) {
                            $query->where('thickness_mm', $thickness);
                        }

                        if ($width === null && $length === null) {
                            return $query;
                        }

                        // Piezas con esa medida o inferior (admite rotación).
                        return $query->where(function (Builder $inner) use ($width, $length): void {
                            if ($width !== null && $length !== null) {
                                $inner
                                    ->where(function (Builder $q) use ($width, $length): void {
                                        $q->where('width_cm', '<=', $width)
                                            ->where('length_cm', '<=', $length);
                                    })
                                    ->orWhere(function (Builder $q) use ($width, $length): void {
                                        $q->where('width_cm', '<=', $length)
                                            ->where('length_cm', '<=', $width);
                                    });

                                return;
                            }

                            if ($width !== null) {
                                $inner->where(function (Builder $q) use ($width): void {
                                    $q->where('width_cm', '<=', $width)
                                        ->orWhere('length_cm', '<=', $width);
                                });

                                return;
                            }

                            $inner->where(function (Builder $q) use ($length): void {
                                $q->where('width_cm', '<=', $length)
                                    ->orWhere('length_cm', '<=', $length);
                            });
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $parts = [];

                        if (filled($data['width_cm'] ?? null)) {
                            $parts[] = 'Ancho ≤ '.$data['width_cm'].' cm';
                        }

                        if (filled($data['length_cm'] ?? null)) {
                            $parts[] = 'Largo ≤ '.$data['length_cm'].' cm';
                        }

                        if (filled($data['thickness_mm'] ?? null)) {
                            $parts[] = 'Grosor '.$data['thickness_mm'].' mm';
                        }

                        return $parts === [] ? [] : [implode(' · ', $parts)];
                    }),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'reserved' => 'Reservada',
                        'used' => 'Usada',
                    ]),
                SelectFilter::make('origin')
                    ->label('Origen')
                    ->options([
                        AntRemnantOrigin::Registered->value => AntRemnantOrigin::Registered->getLabel(),
                        AntRemnantOrigin::SystemSobrante->value => AntRemnantOrigin::SystemSobrante->getLabel(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin sobrantes')
            ->emptyStateDescription('Registra una pieza con ID S-XX para localizarla en almacén.');
    }
}
