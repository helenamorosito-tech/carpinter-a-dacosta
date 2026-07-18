<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Concerns;

use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

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
            'furniture_type' => $get('furniture_type'),
            'side_type' => $get('side_type'),
            'configuration' => [
                'module_count' => $get('configuration.module_count'),
                'shelf_count' => $get('configuration.shelf_count'),
                'costado_visto_izquierda' => $get('configuration.costado_visto_izquierda'),
                'costado_visto_derecha' => $get('configuration.costado_visto_derecha'),
                'trunk_shelf_length_cm' => $get('configuration.trunk_shelf_length_cm'),
                'trunk_shelf_depth_cm' => $get('configuration.trunk_shelf_depth_cm'),
                'trunk_shelf_board_mm' => $get('configuration.trunk_shelf_board_mm'),
            ],
            'board_width_cm' => $get('board_width_cm'),
            'board_height_cm' => $get('board_height_cm'),
            'selected_boards' => $get('selected_boards'),
            'back_board_thickness_mm' => $get('back_board_thickness_mm'),
            'back_board_quantity' => $get('back_board_quantity'),
        ];
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
                ->modalHeading('Previsualización del plan de corte')
                ->modalWidth('7xl')
                ->modalContent(function (Get $get): HtmlString {
                    $pdf = app(PresetCutSheetPdfGenerator::class)
                        ->generateFromFormData(self::cutSheetFormDataFromGet($get));

                    return new HtmlString(
                        view('filament.presets.pdf-preview', [
                            'base64' => base64_encode($pdf->output()),
                        ])->render()
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar'),
            Action::make('downloadCutPdf')
                ->label('Descargar PDF de cortes')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function (Get $get) {
                    $pdf = app(PresetCutSheetPdfGenerator::class)
                        ->generateFromFormData(self::cutSheetFormDataFromGet($get));

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        'plan-corte-mueble-'.now()->format('Ymd-His').'.pdf',
                    );
                }),
        ];
    }
}
