<?php

namespace App\Filament\Admin\Resources\AntFurnitureRemnants\Pages;

use App\Filament\Admin\Resources\AntFurnitureRemnants\AntFurnitureRemnantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAntFurnitureRemnant extends EditRecord
{
    protected static string $resource = AntFurnitureRemnantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
