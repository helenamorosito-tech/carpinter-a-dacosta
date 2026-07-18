<?php

namespace App\Services\Carpentry;

use App\Enums\PlacementType;
use App\Support\BoardMaterialOptions;

class PresetCutCalculator
{
    public function __construct(
        private FurnitureCalculator $furnitureCalculator,
        private CutOptimizer $cutOptimizer,
        private CutDiagramSvgGenerator $svgGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     pieces: list<array{label: string, width_cm: float, height_cm: float, quantity: int}>,
     *     cut_plan: array<string, mixed>,
     *     svg_diagram: string,
     *     calculated_boards_count: int,
     *     back_board_quantity: int,
     *     total_boards_needed: int
     * }
     */
    public function calculate(array $data): array
    {
        $configuration = $data['configuration'] ?? [];
        $heightCm = (float) ($data['global_height_cm'] ?? 0);
        $widthCm = (float) ($data['global_width_cm'] ?? 0);
        $depthCm = (float) ($data['global_depth_cm'] ?? 0);

        if ($heightCm <= 0 || $widthCm <= 0 || $depthCm <= 0) {
            return $this->emptyResult((int) ($data['back_board_quantity'] ?? 1));
        }

        $placement = $data['side_type'] ?? PlacementType::EntreParedes->value;
        if ($placement instanceof PlacementType) {
            $placement = $placement->value;
        }

        $selectedBoards = $data['selected_boards'] ?? BoardMaterialOptions::defaultSelected();
        $thicknessCm = BoardMaterialOptions::primaryThicknessCm(
            is_array($selectedBoards) ? $selectedBoards : []
        );

        $furnitureResult = $this->furnitureCalculator->calculate([
            'height_cm' => $heightCm,
            'width_cm' => $widthCm,
            'depth_cm' => $depthCm,
            'placement_type' => (string) $placement,
            'shelf_count' => (int) ($configuration['shelf_count'] ?? 2),
            'shelf_thickness_cm' => $thicknessCm,
            'module_count' => max(1, (int) ($configuration['module_count'] ?? 1)),
        ]);

        $pieces = $furnitureResult['pieces'];
        $trunkLength = (float) ($configuration['trunk_shelf_length_cm'] ?? 0);
        $trunkDepth = (float) ($configuration['trunk_shelf_depth_cm'] ?? 0);

        if ($trunkLength > 0 && $trunkDepth > 0) {
            $trunkBoardMm = (int) ($configuration['trunk_shelf_board_mm'] ?? 16);
            $pieces[] = [
                'label' => "Balda maletero ({$trunkBoardMm} mm)",
                'width_cm' => round($trunkLength, 2),
                'height_cm' => round($trunkDepth, 2),
                'quantity' => 1,
            ];
        }

        $boardWidth = (float) ($data['board_width_cm'] ?? 122);
        $boardHeight = (float) ($data['board_height_cm'] ?? 244);

        $cutPlan = $this->cutOptimizer->optimize(
            $boardWidth,
            $boardHeight,
            $pieces,
        );

        $cutPlan = $this->nameRemnants($cutPlan);

        $backBoardQuantity = max(0, (int) ($data['back_board_quantity'] ?? 1));
        $mainBoards = (int) ($cutPlan['total_boards'] ?? 0);

        return [
            'pieces' => $pieces,
            'cut_plan' => $cutPlan,
            'svg_diagram' => $this->svgGenerator->generate($cutPlan),
            'calculated_boards_count' => $mainBoards,
            'back_board_quantity' => $backBoardQuantity,
            'total_boards_needed' => $mainBoards + $backBoardQuantity,
            'effective_width_cm' => $furnitureResult['effective_width_cm'],
            'door_count' => $furnitureResult['door_count'],
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
            foreach ($board['remnants'] ?? [] as $remnantIndex => $remnant) {
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

    /**
     * @return array{
     *     pieces: list<array{label: string, width_cm: float, height_cm: float, quantity: int}>,
     *     cut_plan: array<string, mixed>,
     *     svg_diagram: string,
     *     calculated_boards_count: int,
     *     back_board_quantity: int,
     *     total_boards_needed: int
     * }
     */
    protected function emptyResult(int $backBoardQuantity): array
    {
        return [
            'pieces' => [],
            'cut_plan' => [
                'boards' => [],
                'total_boards' => 0,
                'used_area_cm2' => 0,
                'waste_area_cm2' => 0,
                'efficiency_percent' => 0,
            ],
            'svg_diagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="80"><text x="20" y="40" font-size="14" fill="#6b7280">Introduce las medidas globales para calcular el corte</text></svg>',
            'calculated_boards_count' => 0,
            'back_board_quantity' => $backBoardQuantity,
            'total_boards_needed' => $backBoardQuantity,
            'effective_width_cm' => 0,
            'door_count' => 0,
        ];
    }
}
