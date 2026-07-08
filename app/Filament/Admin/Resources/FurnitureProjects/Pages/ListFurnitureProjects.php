<?php

namespace App\Filament\Admin\Resources\FurnitureProjects\Pages;

use App\Filament\Admin\Resources\FurnitureProjects\FurnitureProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFurnitureProjects extends ListRecords
{
    protected static string $resource = FurnitureProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
