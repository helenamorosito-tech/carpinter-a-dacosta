<?php

namespace App\Filament\Admin\Resources\FurniturePresets\Pages;

use App\Filament\Admin\Resources\FurniturePresets\Concerns\InteractsWithFurniturePresetFormData;
use App\Filament\Admin\Resources\FurniturePresets\FurniturePresetResource;
use App\Services\Carpentry\PresetCutSheetPdfGenerator;
use App\Services\Carpentry\TechnicalSheetPdfGenerator;
use App\Support\FurnitureTypeCatalog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class EditFurniturePreset extends EditRecord
{
    use InteractsWithFurniturePresetFormData;

    protected static string $resource = FurniturePresetResource::class;

    public function getTitle(): string|Htmlable
    {
        $label = $this->editedFurnitureTypeLabel();

        return filled($label) ? 'Editar: '.$label : 'Editar';
    }

    public function getHeading(): string|Htmlable
    {
        $label = $this->editedFurnitureTypeLabel();
        $title = e('Editar:');

        if (! filled($label)) {
            return new HtmlString('<span class="fi-edit-heading"><span>'.$title.'</span></span>');
        }

        $badge = Blade::render(
            '<x-filament::badge color="orange" tag="span">{{ $label }}</x-filament::badge>',
            ['label' => $label],
        );

        return new HtmlString(
            '<span class="fi-edit-heading"><span>'.$title.'</span>'.$badge.'</span>',
        );
    }

    protected function editedFurnitureTypeLabel(): ?string
    {
        $state = data_get($this->data, 'furniture_type') ?? $this->getRecord()->furniture_type ?? null;
        $resolved = FurnitureTypeCatalog::resolve($state);

        return $resolved['label'] ?? null;
    }

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
