<?php

namespace App\Filament\Admin\Resources\AntFurnitureRemnants\Pages;

use App\Enums\BoardColor;
use App\Filament\Admin\Resources\AntFurnitureRemnants\AntFurnitureRemnantResource;
use App\Models\AntFurnitureRemnant;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAntFurnitureRemnants extends ListRecords
{
    protected static string $resource = AntFurnitureRemnantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo sobrante'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Todos')
                ->badge(fn (): int => AntFurnitureRemnant::query()->count()),
        ];

        foreach (BoardColor::cases() as $color) {
            $tabs[$color->value] = Tab::make($color->getLabel())
                ->badge(fn () => AntFurnitureRemnant::query()->where('color', $color->value)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('color', $color->value));
        }

        return $tabs;
    }
}
