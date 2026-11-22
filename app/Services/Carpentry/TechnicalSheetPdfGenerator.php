<?php

namespace App\Services\Carpentry;

use App\Models\FurniturePreset;
use Barryvdh\DomPDF\Facade\Pdf;

class TechnicalSheetPdfGenerator
{
    public function __construct(
        private PresetCutCalculator $calculator,
        private TechnicalSheetBuilder $builder,
        private PresetCutSheetPdfGenerator $previews,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     */
    public function storePreviewFromFormData(array $formData, ?string $filename = null): string
    {
        $pdf = $this->generateFromFormData($formData);
        $slug = str($formData['name'] ?? 'mueble')->slug()->toString();

        return $this->previews->storePreviewBinary(
            $pdf->output(),
            $filename ?? 'ficha-tecnica-'.($slug !== '' ? $slug : 'mueble').'.pdf',
        );
    }

    public function storePreview(FurniturePreset $preset): string
    {
        $pdf = $this->generate($preset);

        return $this->previews->storePreviewBinary(
            $pdf->output(),
            'ficha-tecnica-'.($preset->slug ?: 'mueble').'.pdf',
        );
    }

    public function previewUrl(string $token): string
    {
        return $this->previews->previewUrl($token);
    }

    public function downloadUrl(string $token): string
    {
        return $this->previews->downloadUrl($token);
    }

    /**
     * @param  array<string, mixed>  $formData
     */
    public function generateFromFormData(array $formData)
    {
        $calculation = $this->calculator->calculate($formData);
        $payload = $this->builder->build($formData, $calculation);

        return Pdf::loadView('pdf.technical-sheet', $payload)->setPaper('a4', 'portrait');
    }

    public function generate(FurniturePreset $preset)
    {
        return $this->generateFromFormData($this->formDataFromPreset($preset));
    }

    /**
     * @return array<string, mixed>
     */
    public function formDataFromPreset(FurniturePreset $preset): array
    {
        return [
            'name' => $preset->name,
            'opening_height_m' => $preset->opening_height_m,
            'opening_width_m' => $preset->opening_width_m,
            'opening_depth_cm' => $preset->opening_depth_cm,
            'global_height_cm' => $preset->global_height_cm,
            'global_width_cm' => $preset->global_width_cm,
            'global_depth_cm' => $preset->global_depth_cm,
            'furniture_type' => $preset->furniture_type,
            'side_type' => $preset->side_type?->value ?? $preset->side_type,
            'configuration' => $preset->configuration ?? [],
            'selected_boards' => $preset->selected_boards ?? [],
            'structure_board_thickness_mm' => $preset->structure_board_thickness_mm ?? 16,
            'back_board_thickness_mm' => $preset->back_board_thickness_mm,
            'back_board_quantity' => $preset->back_board_quantity,
            'board_width_cm' => $preset->board_width_cm,
            'board_height_cm' => $preset->board_height_cm,
        ];
    }
}
