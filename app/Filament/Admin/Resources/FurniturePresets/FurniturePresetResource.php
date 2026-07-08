<?php

namespace App\Filament\Admin\Resources\FurniturePresets;

use App\Filament\Admin\Resources\FurniturePresets\Pages\CreateFurniturePreset;
use App\Filament\Admin\Resources\FurniturePresets\Pages\EditFurniturePreset;
use App\Filament\Admin\Resources\FurniturePresets\Pages\ListFurniturePresets;
use App\Filament\Admin\Resources\FurniturePresets\Schemas\FurniturePresetForm;
use App\Filament\Admin\Resources\FurniturePresets\Tables\FurniturePresetsTable;
use App\Models\FurniturePreset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FurniturePresetResource extends Resource
{
    protected static ?string $model = FurniturePreset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Muebles/Ejemplos';

    protected static ?string $modelLabel = 'ejemplo';

    protected static ?string $pluralModelLabel = 'muebles/ejemplos';

    protected static string|UnitEnum|null $navigationGroup = 'Carpintería';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return FurniturePresetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FurniturePresetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFurniturePresets::route('/'),
            'create' => CreateFurniturePreset::route('/create'),
            'edit' => EditFurniturePreset::route('/{record}/edit'),
        ];
    }
}
