<?php

namespace App\Services\Carpentry;

use App\Enums\PlacementType;
use App\Models\FurniturePreset;
use App\Support\MeasureFormat;

class PresetCutCalculator
{
    public const DEFAULT_BACK_BOARD_THICKNESS_MM = 10;

    public const DEFAULT_STRUCTURE_BOARD_THICKNESS_MM = 16;

    /** Tamaño máximo de tablero: 1,22 × 2,44 m */
    public const MAX_BOARD_WIDTH_CM = 122.0;

    public const MAX_BOARD_HEIGHT_CM = 244.0;

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
     *     back_board_thickness_mm: int,
     *     structure_board_thickness_mm: int,
     *     total_boards_needed: int,
     *     back_pieces: list<array{label: string, width_cm: float, height_cm: float, quantity: int}>
     * }
     */
    public function calculate(array $data): array
    {
        $configuration = $data['configuration'] ?? [];
        $heightCm = (float) ($data['global_height_cm'] ?? 0);
        $widthCm = (float) ($data['global_width_cm'] ?? 0);
        $depthCm = (float) ($data['global_depth_cm'] ?? 0);
        $backThicknessMm = (int) ($data['back_board_thickness_mm'] ?? self::DEFAULT_BACK_BOARD_THICKNESS_MM);
        $structureThicknessMm = (int) ($data['structure_board_thickness_mm'] ?? self::DEFAULT_STRUCTURE_BOARD_THICKNESS_MM);

        if ($heightCm <= 0 || $widthCm <= 0 || $depthCm <= 0) {
            return $this->emptyResult(
                max(1, (int) ($data['back_board_quantity'] ?? 1)),
                $backThicknessMm,
                $structureThicknessMm,
            );
        }

        $placement = $data['side_type'] ?? PlacementType::EntreParedes->value;
        if ($placement instanceof PlacementType) {
            $placement = $placement->value;
        }

        $thicknessCm = $structureThicknessMm / 10;
        $fondoCount = max(1, (int) ($data['back_board_quantity'] ?? 1));

        $furnitureResult = $this->furnitureCalculator->calculate([
            'height_cm' => $heightCm,
            'width_cm' => $widthCm,
            'depth_cm' => $depthCm,
            'placement_type' => (string) $placement,
            'shelf_count' => (int) ($configuration['shelf_count'] ?? 2),
            'shelf_thickness_cm' => $thicknessCm,
            'module_count' => max(1, (int) ($configuration['module_count'] ?? 1)),
            'door_count' => ElevationFront::doorCount($data['furniture_type'] ?? null, (string) ($data['name'] ?? '')),
            // Cortes base de cualquier mueble: techo, base, laterales y fondo.
            'include_interior_pieces' => false,
            'back_board_quantity' => $fondoCount,
        ]);

        $structuralPieces = [];

        foreach ($furnitureResult['pieces'] as $piece) {
            if (($piece['board_role'] ?? null) === 'back') {
                continue;
            }

            $structuralPieces[] = [
                'label' => $piece['label'],
                'width_cm' => $piece['width_cm'],
                'height_cm' => $piece['height_cm'],
                'quantity' => $piece['quantity'],
            ];
        }

        // Fondos: por defecto 1 pieza en 1 tablero normal (mismo formato 122×244).
        $backPieces = [[
            'label' => 'Fondo del mueble',
            'width_cm' => round($widthCm, 2),
            'height_cm' => round($heightCm, 2),
            'quantity' => 1,
        ]];

        // Accesorio: balda maletero — por defecto ancho global − 6,5 cm y profundidad global − 9 cm, tablero 16 mm.
        $trunkBoardMm = (int) ($configuration['trunk_shelf_board_mm'] ?? 16);
        $trunkLength = (float) ($configuration['trunk_shelf_length_cm'] ?? 0);
        $trunkDepth = (float) ($configuration['trunk_shelf_depth_cm'] ?? 0);

        if ($trunkLength <= 0) {
            $trunkLength = max(0, $widthCm - 6.5);
        }

        if ($trunkDepth <= 0) {
            $trunkDepth = max(0, $depthCm - 9.0);
        }

        if ($trunkLength > 0 && $trunkDepth > 0) {
            $structuralPieces[] = [
                'label' => "Balda maletero ({$trunkBoardMm} mm)",
                'width_cm' => round($trunkLength, 2),
                'height_cm' => round($trunkDepth, 2),
                'quantity' => 1,
            ];
        }

        // Baldas adicionales (aparte de la maletero): una pieza por cada ficha del listado.
        $additionalShelves = FurniturePreset::normalizeAdditionalShelves($configuration, $depthCm, $widthCm);

        foreach ($additionalShelves as $index => $shelf) {
            $width = (float) ($shelf['width_cm'] ?? 0);
            $depth = (float) ($shelf['depth_cm'] ?? 0);
            $boardMm = (int) ($shelf['board_mm'] ?? 16);

            if ($width <= 0 || $depth <= 0) {
                continue;
            }

            $n = $index + 1;
            $structuralPieces[] = [
                'label' => "Balda adicional {$n} ({$boardMm} mm)",
                'width_cm' => round($width, 2),
                'height_cm' => round($depth, 2),
                'quantity' => 1,
            ];
        }

        // Laterales interiores: solo si el usuario agregó alguno; corte = profundidad global × alto.
        $interiorLaterals = FurniturePreset::normalizeInteriorLaterals($configuration, $heightCm);

        foreach ($interiorLaterals as $index => $lateral) {
            $lateralHeight = (float) ($lateral['height_cm'] ?? 0);
            $boardMm = (int) ($lateral['board_mm'] ?? FurniturePreset::resolveInteriorLateralBoardMm($lateral));

            if ($lateralHeight <= 0 || $depthCm <= 0) {
                continue;
            }

            $n = $index + 1;
            $structuralPieces[] = [
                'label' => "Lateral interior {$n} ({$boardMm} mm)",
                'width_cm' => round($depthCm, 2),
                'height_cm' => round($lateralHeight, 2),
                'quantity' => 1,
            ];
        }

        $boardWidth = min(
            (float) ($data['board_width_cm'] ?? self::MAX_BOARD_WIDTH_CM),
            self::MAX_BOARD_WIDTH_CM,
        );
        $boardHeight = min(
            (float) ($data['board_height_cm'] ?? self::MAX_BOARD_HEIGHT_CM),
            self::MAX_BOARD_HEIGHT_CM,
        );

        // Ningún corte de estructura puede superar el tablero máximo 1,22 × 2,44 m.
        $structurePanels = $this->panelizePiecesToBoard($structuralPieces, $boardWidth, $boardHeight);

        $mainCutPlan = $this->withoutEmptyBoards($this->nameRemnants(
            $this->cutOptimizer->optimize($boardWidth, $boardHeight, $structurePanels),
            'estructura',
        ));

        // Fondo: 1 corte sobre 1 tablero normal (sin partir en paneles).
        $backCutPlan = $this->buildSingleFondoCutPlan($widthCm, $heightCm, $boardWidth, $boardHeight);
        $backPanels = $backCutPlan['panels'] ?? [];
        unset($backCutPlan['panels']);

        $stockBoardsForFondo = 1;
        $backBoardQuantity = max(1, $fondoCount);

        $cutPlan = $this->mergeCutPlans(
            $mainCutPlan,
            $backCutPlan,
            $structureThicknessMm,
            $backThicknessMm,
            $boardWidth,
            $boardHeight,
        );
        $cutPlan = $this->assignRemnantCodes($cutPlan);

        $mainBoards = (int) ($mainCutPlan['total_boards'] ?? 0);

        // Listado lógico: piezas de estructura + 1 fondo (medida global).
        // Si el usuario pide más fondos, se listan en cantidad pero el corte usa 1 tablero.
        $backPieces[0]['quantity'] = $backBoardQuantity;
        $allPieces = array_merge($structuralPieces, $backPieces);

        return [
            'pieces' => $allPieces,
            'back_pieces' => $backPieces,
            'back_panels' => $backPanels,
            'fondo_count' => $fondoCount,
            'cut_plan' => $cutPlan,
            'svg_diagram' => $this->svgGenerator->generate($cutPlan),
            'calculated_boards_count' => $mainBoards,
            'back_board_quantity' => $backBoardQuantity,
            'back_stock_boards' => $stockBoardsForFondo,
            'back_board_thickness_mm' => $backThicknessMm,
            'structure_board_thickness_mm' => $structureThicknessMm,
            'total_boards_needed' => $mainBoards + $stockBoardsForFondo,
            'board_width_cm' => $boardWidth,
            'board_height_cm' => $boardHeight,
            'effective_width_cm' => $furnitureResult['effective_width_cm'],
            'door_count' => $furnitureResult['door_count'],
        ];
    }

    /**
     * @param  array<string, mixed>  $main
     * @param  array<string, mixed>  $back
     * @return array<string, mixed>
     */
    protected function mergeCutPlans(
        array $main,
        array $back,
        int $structureThicknessMm,
        int $backThicknessMm,
        float $boardWidthCm = self::MAX_BOARD_WIDTH_CM,
        float $boardHeightCm = self::MAX_BOARD_HEIGHT_CM,
    ): array {
        $boards = [];
        $globalIndex = 1;

        foreach ($main['boards'] ?? [] as $board) {
            $boards[] = [
                ...$board,
                'index' => $globalIndex,
                'title' => "Tablero {$globalIndex} ({$structureThicknessMm} mm)",
                'section_title' => "Cortes de tablero ({$structureThicknessMm} mm)",
                'board_kind' => 'estructura',
            ];
            $globalIndex++;
        }

        $backBoards = $back['boards'] ?? [];
        $boardSizeLabel = MeasureFormat::pairDot($boardWidthCm, $boardHeightCm).' cm';

        foreach ($backBoards as $board) {
            $boards[] = [
                ...$board,
                'index' => $globalIndex,
                'title' => "Tablero {$globalIndex} ({$backThicknessMm} mm)",
                'section_title' => "Corte del fondo del mueble ({$backThicknessMm} mm) — tablero {$boardSizeLabel}",
                'board_kind' => 'fondo',
            ];
            $globalIndex++;
        }

        $used = (float) ($main['used_area_cm2'] ?? 0) + (float) ($back['used_area_cm2'] ?? 0);
        $waste = (float) ($main['waste_area_cm2'] ?? 0) + (float) ($back['waste_area_cm2'] ?? 0);
        $totalArea = $used + $waste;

        return [
            'boards' => $boards,
            'total_boards' => count($boards),
            'used_area_cm2' => round($used, 2),
            'waste_area_cm2' => round($waste, 2),
            'efficiency_percent' => $totalArea > 0 ? round(($used / $totalArea) * 100, 2) : 0,
        ];
    }

    /**
     * Asigna índices locales a tableros (los sobrantes se codifican después).
     *
     * @param  array<string, mixed>  $cutPlan
     * @return array<string, mixed>
     */
    protected function nameRemnants(array $cutPlan, string $prefix): array
    {
        $boards = $cutPlan['boards'] ?? [];

        foreach ($boards as $boardIndex => $board) {
            $boards[$boardIndex]['local_index'] = $board['index'] ?? ($boardIndex + 1);
        }

        $cutPlan['boards'] = $boards;

        return $cutPlan;
    }

    /**
     * Identifica cada sobrante como S-01, S-02, … (global en el plan).
     *
     * @param  array<string, mixed>  $cutPlan
     * @return array<string, mixed>
     */
    protected function assignRemnantCodes(array $cutPlan): array
    {
        $code = 1;

        foreach ($cutPlan['boards'] ?? [] as $boardIndex => $board) {
            foreach ($board['remnants'] ?? [] as $remnantIndex => $remnant) {
                $cutPlan['boards'][$boardIndex]['remnants'][$remnantIndex]['label'] = sprintf('S-%02d', $code);
                $code++;
            }
        }

        return $cutPlan;
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyCutPlan(): array
    {
        return [
            'boards' => [],
            'total_boards' => 0,
            'used_area_cm2' => 0,
            'waste_area_cm2' => 0,
            'efficiency_percent' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function requestedBackBoardQuantity(array $data): ?int
    {
        if (! array_key_exists('back_board_quantity', $data) || $data['back_board_quantity'] === null || $data['back_board_quantity'] === '') {
            return null;
        }

        return max(0, (int) $data['back_board_quantity']);
    }

    /**
     * Elimina tableros sin piezas colocadas (p. ej. cuando una pieza no cabía).
     *
     * @param  array<string, mixed>  $cutPlan
     * @return array<string, mixed>
     */
    protected function withoutEmptyBoards(array $cutPlan): array
    {
        $boards = array_values(array_filter(
            $cutPlan['boards'] ?? [],
            fn (array $board): bool => ($board['placements'] ?? []) !== [],
        ));

        foreach ($boards as $index => $board) {
            $boards[$index]['index'] = $index + 1;
            $boards[$index]['local_index'] = $board['local_index'] ?? ($index + 1);
        }

        $used = (float) ($cutPlan['used_area_cm2'] ?? 0);
        $waste = (float) ($cutPlan['waste_area_cm2'] ?? 0);

        if ($boards !== []) {
            $used = 0.0;
            $waste = 0.0;
            foreach ($boards as $board) {
                $boardArea = ((float) $board['width_cm']) * ((float) $board['height_cm']);
                $usedOnBoard = array_reduce(
                    $board['placements'] ?? [],
                    fn (float $carry, array $placement): float => $carry + (((float) $placement['width']) * ((float) $placement['height'])),
                    0.0,
                );
                $used += $usedOnBoard;
                $waste += max(0, $boardArea - $usedOnBoard);
            }
        }

        $totalArea = $used + $waste;

        $cutPlan['boards'] = $boards;
        $cutPlan['total_boards'] = count($boards);
        $cutPlan['used_area_cm2'] = round($used, 2);
        $cutPlan['waste_area_cm2'] = round($waste, 2);
        $cutPlan['efficiency_percent'] = $totalArea > 0 ? round(($used / $totalArea) * 100, 2) : 0;

        return $cutPlan;
    }

    /**
     * Fondo del mueble: exactamente 1 tablero normal y 1 solo corte (sin panelizar).
     *
     * @return array<string, mixed>
     */
    protected function buildSingleFondoCutPlan(
        float $widthCm,
        float $heightCm,
        float $boardWidthCm,
        float $boardHeightCm,
    ): array {
        if ($widthCm <= 0 || $heightCm <= 0) {
            return [...$this->emptyCutPlan(), 'panels' => []];
        }

        [$pieceW, $pieceH] = $this->fitSingleCutOnBoard(
            round($widthCm, 2),
            round($heightCm, 2),
            $boardWidthCm,
            $boardHeightCm,
        );

        $panels = [[
            'label' => 'Fondo del mueble',
            'width_cm' => $pieceW,
            'height_cm' => $pieceH,
            'quantity' => 1,
        ]];

        $plan = $this->withoutEmptyBoards($this->nameRemnants(
            $this->cutOptimizer->optimize($boardWidthCm, $boardHeightCm, $panels),
            'fondo',
        ));

        $boards = array_values($plan['boards'] ?? []);
        if (count($boards) > 1) {
            $boards = [$boards[0]];
        }

        if ($boards === []) {
            $boards = [[
                'index' => 1,
                'local_index' => 1,
                'width_cm' => $boardWidthCm,
                'height_cm' => $boardHeightCm,
                'placements' => [[
                    'label' => 'Fondo del mueble',
                    'x' => 0.0,
                    'y' => 0.0,
                    'width' => $pieceW,
                    'height' => $pieceH,
                    'rotated' => false,
                ]],
                'remnants' => [[
                    'x' => $pieceW < $boardWidthCm ? $pieceW : 0.0,
                    'y' => $pieceW < $boardWidthCm ? 0.0 : $pieceH,
                    'width' => $pieceW < $boardWidthCm ? round($boardWidthCm - $pieceW, 2) : $boardWidthCm,
                    'height' => $pieceW < $boardWidthCm ? $boardHeightCm : round($boardHeightCm - $pieceH, 2),
                ]],
            ]];

            // Remnant filter: drop if too small.
            if (($boards[0]['remnants'][0]['width'] ?? 0) < 15 || ($boards[0]['remnants'][0]['height'] ?? 0) < 15) {
                $boards[0]['remnants'] = [];
            }
        }

        $used = array_reduce(
            $boards[0]['placements'] ?? [],
            fn (float $carry, array $p): float => $carry + ((float) $p['width'] * (float) $p['height']),
            0.0,
        );
        $boardArea = $boardWidthCm * $boardHeightCm;

        return [
            'boards' => $boards,
            'total_boards' => 1,
            'used_area_cm2' => round($used, 2),
            'waste_area_cm2' => round(max(0, $boardArea - $used), 2),
            'efficiency_percent' => $boardArea > 0 ? round(($used / $boardArea) * 100, 2) : 0,
            'panels' => $panels,
        ];
    }

    /**
     * Ajusta el fondo a un único rectángulo que cabe en el tablero (con rotación si ayuda).
     *
     * @return array{0: float, 1: float}
     */
    protected function fitSingleCutOnBoard(
        float $widthCm,
        float $heightCm,
        float $boardWidthCm,
        float $boardHeightCm,
    ): array {
        if ($this->fitsOnBoard($widthCm, $heightCm, $boardWidthCm, $boardHeightCm)) {
            return [$widthCm, $heightCm];
        }

        // Escala uniforme en la orientación que mejor aprovecha el tablero.
        $scaleAsIs = min($boardWidthCm / max($widthCm, 0.01), $boardHeightCm / max($heightCm, 0.01));
        $scaleRotated = min($boardWidthCm / max($heightCm, 0.01), $boardHeightCm / max($widthCm, 0.01));

        if ($scaleRotated > $scaleAsIs) {
            return [
                round($heightCm * $scaleRotated, 2),
                round($widthCm * $scaleRotated, 2),
            ];
        }

        return [
            round($widthCm * $scaleAsIs, 2),
            round($heightCm * $scaleAsIs, 2),
        ];
    }

    /**
     * Divide piezas mayores que el tablero en paneles que sí caben (p. ej. laterales altos).
     *
     * @param  list<array{label: string, width_cm: float, height_cm: float, quantity: int}>  $pieces
     * @return list<array{label: string, width_cm: float, height_cm: float, quantity: int}>
     */
    protected function panelizePiecesToBoard(array $pieces, float $boardWidthCm, float $boardHeightCm): array
    {
        $panels = [];

        foreach ($pieces as $piece) {
            $quantity = max(1, (int) ($piece['quantity'] ?? 1));

            for ($q = 1; $q <= $quantity; $q++) {
                $baseLabel = $quantity > 1
                    ? sprintf('%s #%d', $piece['label'], $q)
                    : (string) $piece['label'];

                $parts = $this->splitRectangleToFitBoard(
                    (float) $piece['width_cm'],
                    (float) $piece['height_cm'],
                    $boardWidthCm,
                    $boardHeightCm,
                );

                foreach ($parts as $index => $part) {
                    $panels[] = [
                        'label' => count($parts) > 1
                            ? sprintf('%s (parte %d/%d)', $baseLabel, $index + 1, count($parts))
                            : $baseLabel,
                        'width_cm' => $part['width'],
                        'height_cm' => $part['height'],
                        'quantity' => 1,
                    ];
                }
            }
        }

        return $panels;
    }

    /**
     * @return list<array{width: float, height: float}>
     */
    protected function splitRectangleToFitBoard(
        float $widthCm,
        float $heightCm,
        float $boardWidthCm,
        float $boardHeightCm,
    ): array {
        if ($widthCm <= 0 || $heightCm <= 0) {
            return [];
        }

        if ($this->fitsOnBoard($widthCm, $heightCm, $boardWidthCm, $boardHeightCm)) {
            return [[
                'width' => round($widthCm, 2),
                'height' => round($heightCm, 2),
            ]];
        }

        $asIs = $this->splitIntoBalancedGrid($widthCm, $heightCm, $boardWidthCm, $boardHeightCm);
        $rotated = $this->splitIntoBalancedGrid($heightCm, $widthCm, $boardWidthCm, $boardHeightCm);
        $rotated = array_map(
            fn (array $part): array => [
                'width' => $part['height'],
                'height' => $part['width'],
            ],
            $rotated,
        );

        return $this->preferBetterPanelization($asIs, $rotated);
    }

    /**
     * Parte el rectángulo en una rejilla equilibrada (evita tiras finas tipo 122+8).
     *
     * @return list<array{width: float, height: float}>
     */
    protected function splitIntoBalancedGrid(
        float $widthCm,
        float $heightCm,
        float $maxWidthCm,
        float $maxHeightCm,
    ): array {
        $cols = max(1, (int) ceil($widthCm / max(0.01, $maxWidthCm)));
        $rows = max(1, (int) ceil($heightCm / max(0.01, $maxHeightCm)));

        $parts = [];
        $widthUsed = 0.0;

        for ($col = 0; $col < $cols; $col++) {
            $partWidth = $col === $cols - 1
                ? round($widthCm - $widthUsed, 2)
                : round($widthCm / $cols, 2);
            $widthUsed = round($widthUsed + $partWidth, 2);
            $heightUsed = 0.0;

            for ($row = 0; $row < $rows; $row++) {
                $partHeight = $row === $rows - 1
                    ? round($heightCm - $heightUsed, 2)
                    : round($heightCm / $rows, 2);
                $heightUsed = round($heightUsed + $partHeight, 2);

                if ($partWidth > 0.01 && $partHeight > 0.01) {
                    $parts[] = [
                        'width' => $partWidth,
                        'height' => $partHeight,
                    ];
                }
            }
        }

        return $parts;
    }

    /**
     * @param  list<array{width: float, height: float}>  $a
     * @param  list<array{width: float, height: float}>  $b
     * @return list<array{width: float, height: float}>
     */
    protected function preferBetterPanelization(array $a, array $b): array
    {
        if ($a === []) {
            return $b;
        }

        if ($b === []) {
            return $a;
        }

        $score = function (array $parts): array {
            $minSide = min(array_map(
                fn (array $part): float => min((float) $part['width'], (float) $part['height']),
                $parts,
            ));

            return [count($parts), -$minSide];
        };

        return $score($a) <= $score($b) ? $a : $b;
    }

    protected function fitsOnBoard(float $widthCm, float $heightCm, float $boardWidthCm, float $boardHeightCm): bool
    {
        return ($widthCm <= $boardWidthCm && $heightCm <= $boardHeightCm)
            || ($widthCm <= $boardHeightCm && $heightCm <= $boardWidthCm);
    }

    /**
     * Pads a cut plan with empty boards when the user requests more than the optimizer needs.
     *
     * @param  array<string, mixed>  $cutPlan
     * @return array<string, mixed>
     */
    protected function ensureBoardCount(
        array $cutPlan,
        int $targetCount,
        float $boardWidthCm,
        float $boardHeightCm,
        string $prefix,
    ): array {
        $boards = $cutPlan['boards'] ?? [];
        $current = count($boards);

        if ($targetCount <= $current) {
            $cutPlan['total_boards'] = $current;

            return $cutPlan;
        }

        for ($i = $current; $i < $targetCount; $i++) {
            $boards[] = [
                'index' => $i + 1,
                'local_index' => $i + 1,
                'width_cm' => $boardWidthCm,
                'height_cm' => $boardHeightCm,
                'placements' => [],
                'remnants' => [[
                    'x' => 0.0,
                    'y' => 0.0,
                    'width' => $boardWidthCm,
                    'height' => $boardHeightCm,
                    'label' => 'S-00',
                ]],
            ];
        }

        $boardArea = $boardWidthCm * $boardHeightCm;
        $used = (float) ($cutPlan['used_area_cm2'] ?? 0);
        $totalArea = $boardArea * count($boards);

        $cutPlan['boards'] = $boards;
        $cutPlan['total_boards'] = count($boards);
        $cutPlan['waste_area_cm2'] = round(max(0, $totalArea - $used), 2);
        $cutPlan['efficiency_percent'] = $totalArea > 0 ? round(($used / $totalArea) * 100, 2) : 0;

        return $cutPlan;
    }

    /**
     * @return array{
     *     pieces: list<array{label: string, width_cm: float, height_cm: float, quantity: int}>,
     *     cut_plan: array<string, mixed>,
     *     svg_diagram: string,
     *     calculated_boards_count: int,
     *     back_board_quantity: int,
     *     back_board_thickness_mm: int,
     *     total_boards_needed: int,
     *     back_pieces: list<array{label: string, width_cm: float, height_cm: float, quantity: int}>
     * }
     */
    protected function emptyResult(
        int $backBoardQuantity,
        int $backThicknessMm = self::DEFAULT_BACK_BOARD_THICKNESS_MM,
        int $structureThicknessMm = self::DEFAULT_STRUCTURE_BOARD_THICKNESS_MM,
    ): array {
        return [
            'pieces' => [],
            'back_pieces' => [],
            'back_panels' => [],
            'fondo_count' => $backBoardQuantity,
            'back_stock_boards' => 0,
            'cut_plan' => $this->emptyCutPlan(),
            'svg_diagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="80"><text x="20" y="40" font-size="14" fill="#6b7280">Introduce las medidas globales para calcular el corte</text></svg>',
            'calculated_boards_count' => 0,
            'back_board_quantity' => $backBoardQuantity,
            'back_board_thickness_mm' => $backThicknessMm,
            'structure_board_thickness_mm' => $structureThicknessMm,
            'total_boards_needed' => $backBoardQuantity,
            'effective_width_cm' => 0,
            'door_count' => 0,
        ];
    }
}
