<?php

namespace App\Services\Carpentry;

use App\Enums\FurnitureCabinetType;
use App\Enums\PlacementType;
use App\Models\FurniturePreset;
use App\Support\BoardMaterialOptions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PresetCutSheetPdfGenerator
{
    public function __construct(
        private PresetCutCalculator $calculator,
        private CutDiagramSvgGenerator $svgGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     */
    public function generateFromFormData(array $formData)
    {
        $calculation = $this->calculator->calculate($formData);
        $payload = $this->buildPayload($formData, $calculation);

        return Pdf::loadView('pdf.preset-cut-sheet', $payload)
            ->setPaper('a4', 'portrait');
    }

    public function generate(FurniturePreset $preset)
    {
        $formData = [
            'name' => $preset->name,
            'opening_height_m' => $preset->opening_height_m,
            'opening_width_m' => $preset->opening_width_m,
            'opening_depth_cm' => $preset->opening_depth_cm,
            'global_height_cm' => $preset->global_height_cm,
            'global_width_cm' => $preset->global_width_cm,
            'global_depth_cm' => $preset->global_depth_cm,
            'furniture_type' => $preset->furniture_type?->value,
            'side_type' => $preset->side_type?->value,
            'configuration' => $preset->configuration ?? [],
            'board_width_cm' => $preset->board_width_cm,
            'board_height_cm' => $preset->board_height_cm,
            'selected_boards' => $preset->selected_boards ?? [],
            'back_board_thickness_mm' => $preset->back_board_thickness_mm,
            'back_board_quantity' => $preset->back_board_quantity,
        ];

        $calculation = [
            'pieces' => $preset->pieces ?? [],
            'cut_plan' => $preset->cut_plan ?? [],
            'svg_diagram' => $preset->svg_diagram,
            'calculated_boards_count' => $preset->calculated_boards_count ?? 0,
            'back_board_quantity' => $preset->back_board_quantity ?? 0,
            'total_boards_needed' => (int) ($preset->calculated_boards_count ?? 0) + (int) ($preset->back_board_quantity ?? 0),
        ];

        if (empty($calculation['cut_plan']['boards'])) {
            $calculation = $this->calculator->calculate($formData);
        } else {
            $calculation['cut_plan'] = $this->nameRemnants($calculation['cut_plan']);
            $calculation['svg_diagram'] = $this->svgGenerator->generate($calculation['cut_plan']);
        }

        $payload = $this->buildPayload($formData, $calculation);

        return Pdf::loadView('pdf.preset-cut-sheet', $payload)
            ->setPaper('a4', 'portrait');
    }

    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $calculation
     * @return array<string, mixed>
     */
    protected function buildPayload(array $formData, array $calculation): array
    {
        $cutPlan = $this->nameRemnants($calculation['cut_plan'] ?? []);
        $pieces = $calculation['pieces'] ?? [];
        $configuration = $formData['configuration'] ?? [];

        $furnitureType = FurnitureCabinetType::tryFromState(
            is_string($formData['furniture_type'] ?? null) ? $formData['furniture_type'] : null
        );

        $sideType = PlacementType::tryFrom((string) ($formData['side_type'] ?? ''));

        $selectedBoards = BoardMaterialOptions::parseMany(
            is_array($formData['selected_boards'] ?? null) ? $formData['selected_boards'] : []
        );

        $boardsToUse = [];
        foreach ($cutPlan['boards'] ?? [] as $board) {
            $boardsToUse[] = [
                'name' => 'Tablero '.$board['index'],
                'type' => 'Principal',
                'width_cm' => $board['width_cm'],
                'height_cm' => $board['height_cm'],
                'pieces_count' => count($board['placements'] ?? []),
                'remnants_count' => count($board['remnants'] ?? []),
            ];
        }

        $backQty = (int) ($formData['back_board_quantity'] ?? $calculation['back_board_quantity'] ?? 0);
        $backThickness = (int) ($formData['back_board_thickness_mm'] ?? 10);

        if ($backQty > 0) {
            $boardsToUse[] = [
                'name' => 'Tablero de fondo',
                'type' => "Fondo {$backThickness} mm",
                'width_cm' => (float) ($formData['board_width_cm'] ?? 122),
                'height_cm' => (float) ($formData['board_height_cm'] ?? 244),
                'pieces_count' => $backQty,
                'remnants_count' => 0,
                'quantity' => $backQty,
            ];
        }

        $namedPieces = [];
        $pieceIndex = 1;
        foreach ($pieces as $piece) {
            $qty = max(1, (int) ($piece['quantity'] ?? 1));
            for ($i = 1; $i <= $qty; $i++) {
                $suffix = $qty > 1 ? " #{$i}" : '';
                $namedPieces[] = [
                    'name' => ($piece['label'] ?? 'Pieza').$suffix,
                    'code' => 'P'.str_pad((string) $pieceIndex, 2, '0', STR_PAD_LEFT),
                    'width_cm' => $piece['width_cm'],
                    'height_cm' => $piece['height_cm'],
                    'area_cm2' => round(((float) $piece['width_cm']) * ((float) $piece['height_cm']), 2),
                ];
                $pieceIndex++;
            }
        }

        $namedRemnants = [];
        foreach ($cutPlan['boards'] ?? [] as $board) {
            foreach ($board['remnants'] ?? [] as $remnant) {
                $namedRemnants[] = [
                    'name' => $remnant['label'] ?? 'Sobrante',
                    'board' => 'Tablero '.$board['index'],
                    'width_cm' => $remnant['width'],
                    'height_cm' => $remnant['height'],
                    'x' => $remnant['x'],
                    'y' => $remnant['y'],
                    'area_cm2' => round(((float) $remnant['width']) * ((float) $remnant['height']), 2),
                ];
            }
        }

        $title = $formData['name']
            ?? ($furnitureType?->getLabel() ?? 'Mueble').' — plan de corte';

        $sideDetail = $sideType?->getLabel() ?? '—';
        if ($sideType === PlacementType::CostadoVisto) {
            $sides = [];
            if (! empty($configuration['costado_visto_izquierda'])) {
                $sides[] = 'izquierda';
            }
            if (! empty($configuration['costado_visto_derecha'])) {
                $sides[] = 'derecha';
            }
            if ($sides !== []) {
                $sideDetail .= ' ('.implode(' y ', $sides).')';
            }
        }

        $svg = $calculation['svg_diagram']
            ?? $this->svgGenerator->generate($cutPlan);

        // Mismos gráficos que el recurso: un SVG por tablero con la misma escala por defecto.
        $boardDiagrams = [];
        foreach ($cutPlan['boards'] ?? [] as $board) {
            $boardDiagrams[] = [
                'index' => $board['index'],
                'width_cm' => $board['width_cm'],
                'height_cm' => $board['height_cm'],
                'svg' => $this->svgGenerator->generate([
                    'boards' => [$board],
                ]),
            ];
        }

        return [
            'title' => $title,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'furnitureType' => $furnitureType?->getLabel() ?? '—',
            'opening' => [
                'height_m' => $formData['opening_height_m'] ?? null,
                'width_m' => $formData['opening_width_m'] ?? null,
                'depth_cm' => $formData['opening_depth_cm'] ?? null,
            ],
            'global' => [
                'height_cm' => $formData['global_height_cm'] ?? null,
                'width_cm' => $formData['global_width_cm'] ?? null,
                'depth_cm' => $formData['global_depth_cm'] ?? null,
            ],
            'sideDetail' => $sideDetail,
            'boardSize' => [
                'width_cm' => $formData['board_width_cm'] ?? 122,
                'height_cm' => $formData['board_height_cm'] ?? 244,
            ],
            'materials' => array_map(fn (array $item): string => $item['label'], $selectedBoards),
            'backBoard' => [
                'thickness_mm' => $backThickness,
                'quantity' => $backQty,
            ],
            'configuration' => $configuration,
            'boardsToUse' => $boardsToUse,
            'namedPieces' => $namedPieces,
            'namedRemnants' => $namedRemnants,
            'cutPlan' => $cutPlan,
            'svg' => $svg,
            'boardDiagrams' => $boardDiagrams,
            'totals' => [
                'main_boards' => (int) ($calculation['calculated_boards_count'] ?? 0),
                'back_boards' => $backQty,
                'total_boards' => (int) ($calculation['total_boards_needed'] ?? 0),
                'efficiency_percent' => $cutPlan['efficiency_percent'] ?? 0,
                'used_area_cm2' => $cutPlan['used_area_cm2'] ?? 0,
                'waste_area_cm2' => $cutPlan['waste_area_cm2'] ?? 0,
            ],
            'filename' => 'plan-corte-'.Str::slug($title).'.pdf',
        ];
    }

    /**
     * @param  array<string, mixed>  $cutPlan
     * @return array<string, mixed>
     */
    protected function nameRemnants(array $cutPlan): array
    {
        $boards = $cutPlan['boards'] ?? [];

        foreach ($boards as $boardIndex => $board) {
            $remnants = $board['remnants'] ?? [];

            foreach ($remnants as $remnantIndex => $remnant) {
                if (! empty($remnant['label'])) {
                    continue;
                }

                $boards[$boardIndex]['remnants'][$remnantIndex]['label'] = sprintf(
                    'Sobrante %d-%d',
                    $board['index'] ?? ($boardIndex + 1),
                    $remnantIndex + 1,
                );
            }
        }

        $cutPlan['boards'] = $boards;

        return $cutPlan;
    }
}
