<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Pages;

use App\Filament\Admin\Resources\FurniturePresets\Concerns\InteractsWithFurniturePresetFormData;
use App\Filament\Admin\Resources\FurniturePresets\FurniturePresetResource;
use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditFurniturePreset extends EditRecord
{
    use InteractsWithFurniturePresetFormData;

    protected static string $resource = FurniturePresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewCutPdf')
                ->label('Previsualizar PDF')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading('Previsualización del plan de corte')
                ->modalWidth('7xl')
                ->modalContent(function (): HtmlString {
                    $pdf = app(PresetCutSheetPdfGenerator::class)
                        ->generateFromFormData($this->form->getStateSnapshot());

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
                ->color('gray')
                ->action(function () {
                    $pdf = app(PresetCutSheetPdfGenerator::class)
                        ->generateFromFormData($this->form->getStateSnapshot());

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        'plan-corte-'.($this->record->slug ?? 'mueble').'.pdf',
                    );
                }),
            DeleteAction::make(),
        ];
    }
}
