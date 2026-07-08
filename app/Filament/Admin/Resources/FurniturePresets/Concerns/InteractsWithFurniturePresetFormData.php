<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Concerns;

use App\Models\FurniturePreset;

trait InteractsWithFurniturePresetFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return FurniturePreset::prepareDataForSave($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return FurniturePreset::prepareDataForFill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return FurniturePreset::prepareDataForSave($data);
    }
}
