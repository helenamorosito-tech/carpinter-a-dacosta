<?php

namespace App\Filament\Admin\Resources\FurnitureProjects\Pages;

use App\Enums\FurnitureWorkStatus;
use App\Filament\Admin\Resources\FurnitureProjects\FurnitureProjectResource;
use App\Services\Carpentry\ProjectCalculationService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateFurnitureProject extends CreateRecord
{
    protected static string $resource = FurnitureProjectResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Filament::auth()->id();
        $data['status'] ??= FurnitureWorkStatus::EnProyecto->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        app(ProjectCalculationService::class)->calculateAndPersist($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return FurnitureProjectResource::getUrl('view', ['record' => $this->record]);
    }
}
