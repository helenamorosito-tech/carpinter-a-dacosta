<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Pages;

use App\Filament\Admin\Resources\FurniturePresets\Concerns\InteractsWithFurniturePresetFormData;
use App\Filament\Admin\Resources\FurniturePresets\FurniturePresetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFurniturePreset extends EditRecord
{
    use InteractsWithFurniturePresetFormData;

    protected static string $resource = FurniturePresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
