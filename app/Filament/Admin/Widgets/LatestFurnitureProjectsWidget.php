<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\FurnitureWorkStatus;
use App\Filament\Admin\Resources\FurnitureProjects\FurnitureProjectResource;
use App\Models\FurnitureProject;
use App\Support\FilamentUiBadges;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestFurnitureProjectsWidget extends TableWidget
{
    protected static ?string $heading = 'ÚLTIMOS 10 MUEBLES';

    protected static ?string $description = 'Últimos trabajos registrados: nombre, precio y estado de obra.';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $ids = FurnitureProject::query()
                    ->latest('updated_at')
                    ->limit(10)
                    ->pluck('id');

                return FurnitureProject::query()
                    ->whereIn('id', $ids)
                    ->latest('updated_at');
            })
            ->paginated(false)
            ->columns([
                FilamentUiBadges::nameColumn(
                    TextColumn::make('name')
                        ->label('Nombre del mueble')
                        ->searchable()
                        ->url(fn (FurnitureProject $record): string => FurnitureProjectResource::getUrl('view', ['record' => $record]))
                ),
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
                ),
                FilamentUiBadges::widthColumn(
                    TextColumn::make('width_cm')
                        ->label('Ancho')
                        ->suffix(' cm')
                ),
                FilamentUiBadges::updatedColumn(
                    TextColumn::make('updated_at')
                        ->label('Actualizado')
                        ->dateTime('d/m/Y H:i')
                        ->sortable()
                ),
            ])
            ->emptyStateHeading('Sin trabajos todavía')
            ->emptyStateDescription('Crea un cálculo de mueble o ejecuta el seeder para ver ejemplos.')
            ->headerActions([
                Action::make('verTodos')
                    ->label('Ver todos')
                    ->url(FurnitureProjectResource::getUrl('index'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray'),
            ]);
    }
}
