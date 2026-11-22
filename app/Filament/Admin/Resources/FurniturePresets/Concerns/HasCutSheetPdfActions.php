<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Concerns;

use App\Models\FurniturePreset;
use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use App\Support\BoardMaterialOptions;
use App\Support\FurnitureTypeCatalog;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Component;

trait HasCutSheetPdfActions
{
    /**
     * @return array<string, mixed>
     */
    public static function cutSheetFormDataFromGet(Get $get): array
    {
        return [
            'name' => null,
            'opening_height_m' => $get('opening_height_m'),
            'opening_width_m' => $get('opening_width_m'),
            'opening_depth_cm' => $get('opening_depth_cm'),
            'global_height_cm' => $get('global_height_cm'),
            'global_width_cm' => $get('global_width_cm'),
            'global_depth_cm' => $get('global_depth_cm'),
            'furniture_type' => FurnitureTypeCatalog::normalizeSlug($get('furniture_type')),
            'side_type' => $get('side_type'),
            'configuration' => [
                'module_count' => $get('configuration.module_count'),
                'shelf_count' => $get('configuration.shelf_count'),
                'costado_visto_izquierda' => $get('configuration.costado_visto_izquierda'),
                'costado_visto_derecha' => $get('configuration.costado_visto_derecha'),
                'trunk_shelf_length_cm' => $get('configuration.trunk_shelf_length_cm'),
                'trunk_shelf_depth_cm' => $get('configuration.trunk_shelf_depth_cm'),
                'trunk_shelf_board_mm' => $get('configuration.trunk_shelf_board_mm'),
                'additional_shelves' => $get('configuration.additional_shelves') ?? [],
                'interior_laterals' => $get('configuration.interior_laterals') ?? [],
                'hanging_bars_count' => $get('configuration.hanging_bars_count'),
                'hanging_bar_length_cm' => $get('configuration.hanging_bar_length_cm'),
                'drawers_count' => $get('configuration.drawers_count'),
                'drawer_height_cm' => $get('configuration.drawer_height_cm'),
                'drawer_width_cm' => $get('configuration.drawer_width_cm'),
                'drawer_depth_cm' => $get('configuration.drawer_depth_cm'),
                'shelf_depth_cm' => $get('configuration.shelf_depth_cm'),
            ],
            'board_width_cm' => $get('board_width_cm'),
            'board_height_cm' => $get('board_height_cm'),
            'selected_boards' => self::normalizeSelectedBoards($get('selected_boards')),
            'structure_board_thickness_mm' => $get('structure_board_thickness_mm') ?? 16,
            'back_board_thickness_mm' => $get('back_board_thickness_mm') ?? FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM,
            'back_board_quantity' => $get('back_board_quantity'),
        ];
    }

    /**
     * @return list<string>
     */
    protected static function normalizeSelectedBoards(mixed $selected): array
    {
        if (is_array($selected)) {
            return array_values(array_filter(array_map('strval', $selected)));
        }

        if (filled($selected)) {
            return [(string) $selected];
        }

        return BoardMaterialOptions::defaultSelected();
    }

    /**
     * @return array<int, Action>
     */
    public static function cutSheetPdfActions(): array
    {
        return [
            Action::make('previewCutPdf')
                ->label('Previsualizar PDF')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action(function (Get $get, Component $livewire): void {
                    $generator = app(PresetCutSheetPdfGenerator::class);
                    $token = $generator->storePreviewFromFormData(self::cutSheetFormDataFromGet($get));
                    $url = $generator->previewUrl($token);

                    $livewire->js('window.open('.json_encode($url).', "_blank")');
                }),
            Action::make('downloadCutPdf')
                ->label('Descargar PDF de cortes')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function (Get $get) {
                    $generator = app(PresetCutSheetPdfGenerator::class);
                    $token = $generator->storePreviewFromFormData(self::cutSheetFormDataFromGet($get));

                    return redirect()->away($generator->downloadUrl($token));
                }),
        ];
    }
}
