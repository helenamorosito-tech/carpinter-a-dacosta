<?php

namespace App\Filament\Admin\Resources\FurnitureProjects\Pages;

use App\Filament\Admin\Resources\FurnitureProjects\FurnitureProjectResource;
use App\Services\Carpentry\CutSheetPdfGenerator;
use App\Services\Carpentry\ProjectCalculationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewFurnitureProject extends ViewRecord
{
    protected static string $resource = FurnitureProjectResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del mueble')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Proyecto'),
                        TextEntry::make('reference')->label('Referencia')->placeholder('—'),
                        TextEntry::make('preset.name')->label('Preset')->placeholder('—'),
                        TextEntry::make('height_cm')->label('Alto')->suffix(' cm'),
                        TextEntry::make('width_cm')->label('Ancho')->suffix(' cm'),
                        TextEntry::make('depth_cm')->label('Fondo')->suffix(' cm'),
                        TextEntry::make('placement_type')->label('Ubicación'),
                        TextEntry::make('door_type')->label('Tipo de puertas'),
                        TextEntry::make('effective_width_cm')->label('Ancho efectivo')->suffix(' cm'),
                        TextEntry::make('shelf_count')->label('Baldas'),
                        TextEntry::make('module_count')->label('Módulos'),
                        TextEntry::make('door_count')->label('Puertas calculadas'),
                        TextEntry::make('material.name')->label('Material')->placeholder('—'),
                    ]),
                Section::make('Resultado del corte')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('cut_plan.total_boards')
                            ->label('Tableros necesarios')
                            ->placeholder('—'),
                        TextEntry::make('cut_plan.efficiency_percent')
                            ->label('Eficiencia')
                            ->suffix('%')
                            ->placeholder('—'),
                        TextEntry::make('cut_plan.waste_area_cm2')
                            ->label('Desperdicio')
                            ->suffix(' cm²')
                            ->placeholder('—'),
                    ]),
                Section::make('Diagrama de cortes')
                    ->schema([
                        ViewEntry::make('svg_diagram')
                            ->label('')
                            ->view('filament.projects.cut-diagram'),
                    ]),
                Section::make('Piezas requeridas')
                    ->schema([
                        ViewEntry::make('pieces')
                            ->label('')
                            ->view('filament.projects.pieces-table'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $pdf = app(CutSheetPdfGenerator::class)->generate($this->record);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        'plan-corte-'.$this->record->id.'.pdf',
                    );
                }),
            Action::make('recalculate')
                ->label('Recalcular')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function (): void {
                    app(ProjectCalculationService::class)->calculateAndPersist($this->record);
                    $this->refreshFormData(['record']);
                }),
            EditAction::make(),
        ];
    }
}
