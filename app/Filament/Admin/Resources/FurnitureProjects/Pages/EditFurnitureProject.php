<?php

namespace App\Filament\Admin\Resources\FurnitureProjects\Pages;

use App\Filament\Admin\Resources\FurnitureProjects\FurnitureProjectResource;
use App\Services\Carpentry\ProjectCalculationService;
use Filament\Resources\Pages\EditRecord;

class EditFurnitureProject extends EditRecord
{
    protected static string $resource = FurnitureProjectResource::class;

    protected function afterSave(): void
    {
        app(ProjectCalculationService::class)->calculateAndPersist($this->record);
    }

    protected function getRedirectUrl(): ?string
    {
        return FurnitureProjectResource::getUrl('view', ['record' => $this->record]);
    }
}
