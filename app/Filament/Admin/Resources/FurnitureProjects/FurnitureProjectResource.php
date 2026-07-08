<?php

namespace App\Filament\Admin\Resources\FurnitureProjects;

use App\Filament\Admin\Resources\FurnitureProjects\Pages\CreateFurnitureProject;
use App\Filament\Admin\Resources\FurnitureProjects\Pages\EditFurnitureProject;
use App\Filament\Admin\Resources\FurnitureProjects\Pages\ListFurnitureProjects;
use App\Filament\Admin\Resources\FurnitureProjects\Pages\ViewFurnitureProject;
use App\Filament\Admin\Resources\FurnitureProjects\Schemas\FurnitureProjectForm;
use App\Filament\Admin\Resources\FurnitureProjects\Tables\FurnitureProjectsTable;
use App\Models\FurnitureProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FurnitureProjectResource extends Resource
{
    protected static ?string $model = FurnitureProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Cálculos de muebles';

    protected static ?string $modelLabel = 'cálculo';

    protected static ?string $pluralModelLabel = 'cálculos de muebles';

    protected static string|UnitEnum|null $navigationGroup = 'Carpintería';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return FurnitureProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FurnitureProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFurnitureProjects::route('/'),
            'create' => CreateFurnitureProject::route('/create'),
            'view' => ViewFurnitureProject::route('/{record}'),
            'edit' => EditFurnitureProject::route('/{record}/edit'),
        ];
    }
}
