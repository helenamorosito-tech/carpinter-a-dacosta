<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Pages;

use App\Filament\Admin\Resources\FurniturePresets\FurniturePresetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFurniturePresets extends ListRecords
{
    protected static string $resource = FurniturePresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear mueble'),
        ];
    }
}
