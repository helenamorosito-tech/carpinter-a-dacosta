<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Pages;

use App\Filament\Admin\Resources\FurniturePresets\Concerns\InteractsWithFurniturePresetFormData;
use App\Filament\Admin\Resources\FurniturePresets\FurniturePresetResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateFurniturePreset extends CreateRecord
{
    use InteractsWithFurniturePresetFormData;

    protected static string $resource = FurniturePresetResource::class;

    protected static ?string $title = 'Crear mueble';

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Crear mueble');
    }
}
