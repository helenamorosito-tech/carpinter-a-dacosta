<?php

namespace App\Filament\Admin\Resources\AntFurnitureRemnants\Pages;

use App\Filament\Admin\Resources\AntFurnitureRemnants\AntFurnitureRemnantResource;
use App\Models\AntFurnitureRemnant;
use Filament\Resources\Pages\CreateRecord;

class CreateAntFurnitureRemnant extends CreateRecord
{
    protected static string $resource = AntFurnitureRemnantResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = AntFurnitureRemnant::nextCode();
        $data['status'] ??= 'available';
        $data['is_active'] ??= true;

        return $data;
    }
}
