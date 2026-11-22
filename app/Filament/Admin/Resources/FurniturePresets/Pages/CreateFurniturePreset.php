<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Pages;

use App\Filament\Admin\Resources\FurniturePresets\Concerns\InteractsWithFurniturePresetFormData;
use App\Filament\Admin\Resources\FurniturePresets\FurniturePresetResource;
use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use App\Services\Carpentry\TechnicalSheetPdfGenerator;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateFurniturePreset extends CreateRecord
{
    use InteractsWithFurniturePresetFormData;

    protected static string $resource = FurniturePresetResource::class;

    protected static ?string $title = 'Crear mueble';

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Crear mueble');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createHeader')
                ->label('Crear mueble')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('create')
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
            Action::make('previewTechnicalSheet')
                ->label('Ficha técnica')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->action(function (): void {
                    $generator = app(TechnicalSheetPdfGenerator::class);
                    $token = $generator->storePreviewFromFormData($this->form->getStateSnapshot());
                    $url = $generator->previewUrl($token);

                    $this->js('window.open('.json_encode($url).', "_blank")');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }
}
