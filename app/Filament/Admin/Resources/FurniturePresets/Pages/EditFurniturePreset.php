<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Pages;

use App\Filament\Admin\Resources\FurniturePresets\Concerns\InteractsWithFurniturePresetFormData;
use App\Filament\Admin\Resources\FurniturePresets\FurniturePresetResource;
use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFurniturePreset extends EditRecord
{
    use InteractsWithFurniturePresetFormData;

    protected static string $resource = FurniturePresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveHeader')
                ->label('Guardar cambios')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save')
                ->keyBindings(['mod+s']),
            Action::make('previewCutPdf')
                ->label('Previsualizar PDF')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action(function (): void {
                    $generator = app(PresetCutSheetPdfGenerator::class);
                    $token = $generator->storePreviewFromFormData($this->form->getStateSnapshot());
                    $url = $generator->previewUrl($token);

                    $this->js('window.open('.json_encode($url).', "_blank")');
                }),
            Action::make('downloadCutPdf')
                ->label('Descargar PDF de cortes')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $generator = app(PresetCutSheetPdfGenerator::class);
                    $token = $generator->storePreviewFromFormData($this->form->getStateSnapshot());

                    return redirect()->away($generator->downloadUrl($token));
                }),
            DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar cambios');
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl('index');
    }
}
