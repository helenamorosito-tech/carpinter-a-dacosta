<?php

namespace App\Filament\Admin\Resources\AntFurnitureRemnants;

use App\Filament\Admin\Resources\AntFurnitureRemnants\Pages\CreateAntFurnitureRemnant;
use App\Filament\Admin\Resources\AntFurnitureRemnants\Pages\EditAntFurnitureRemnant;
use App\Filament\Admin\Resources\AntFurnitureRemnants\Pages\ListAntFurnitureRemnants;
use App\Filament\Admin\Resources\AntFurnitureRemnants\Schemas\AntFurnitureRemnantForm;
use App\Filament\Admin\Resources\AntFurnitureRemnants\Tables\AntFurnitureRemnantsTable;
use App\Models\AntFurnitureRemnant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AntFurnitureRemnantResource extends Resource
{
    protected static ?string $model = AntFurnitureRemnant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Sobrantes';

    protected static ?string $modelLabel = 'sobrante';

    protected static ?string $pluralModelLabel = 'sobrantes';

    protected static ?string $slug = 'sobrantes';

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return AntFurnitureRemnantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AntFurnitureRemnantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAntFurnitureRemnants::route('/'),
            'create' => CreateAntFurnitureRemnant::route('/create'),
            'edit' => EditAntFurnitureRemnant::route('/{record}/edit'),
        ];
    }
}
