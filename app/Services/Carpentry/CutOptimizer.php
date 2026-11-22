<?php

namespace App\Services\Carpentry;

class CutOptimizer
{
    /**
     * @param  list<array{label: string, width_cm: float, height_cm: float, quantity: int}>  $pieces
     * @return array{
     *     boards: list<array{
     *         index: int,
     *         width_cm: float,
     *         height_cm: float,
     *         placements: list<array{label: string, x: float, y: float, width: float, height: float, rotated: bool}>,
     *         remnants: list<array{x: float, y: float, width: float, height: float}>
     *     }>,
     *     total_boards: int,
     *     used_area_cm2: float,
     *     waste_area_cm2: float,
     *     efficiency_percent: float
     * }
     */
    public function optimize(float $boardWidthCm, float $boardHeightCm, array $pieces): array
    {
        $expandedPieces = $this->expandPieces($pieces);

        usort($expandedPieces, fn (array $a, array $b): int => ($b['width_cm'] * $b['height_cm']) <=> ($a['width_cm'] * $a['height_cm']));

        $boards = [];

        foreach ($expandedPieces as $piece) {
            $placed = false;

            foreach ($boards as &$board) {
                $placement = $this->findPlacement($board['free_rects'], $piece['width_cm'], $piece['height_cm']);

                if ($placement === null) {
                    continue;
                }

                $board['placements'][] = [
                    'label' => $piece['label'],
                    'x' => $placement['x'],
                    'y' => $placement['y'],
                    'width' => $placement['width'],
                    'height' => $placement['height'],
                    'rotated' => $placement['rotated'],
                ];

                $board['free_rects'] = $this->splitFreeRects(
                    $board['free_rects'],
                    $placement['x'],
                    $placement['y'],
                    $placement['width'],
                    $placement['height'],
                );

                $placed = true;
                break;
            }
            unset($board);

            if ($placed) {
                continue;
            }

            $boards[] = [
                'index' => count($boards) + 1,
                'width_cm' => $boardWidthCm,
                'height_cm' => $boardHeightCm,
                'placements' => [],
                'free_rects' => [[
                    'x' => 0.0,
                    'y' => 0.0,
                    'width' => $boardWidthCm,
                    'height' => $boardHeightCm,
                ]],
            ];

            $lastIndex = count($boards) - 1;
            $placement = $this->findPlacement($boards[$lastIndex]['free_rects'], $piece['width_cm'], $piece['height_cm']);

            if ($placement === null) {
                continue;
            }

            $boards[$lastIndex]['placements'][] = [
                'label' => $piece['label'],
                'x' => $placement['x'],
                'y' => $placement['y'],
                'width' => $placement['width'],
                'height' => $placement['height'],
                'rotated' => $placement['rotated'],
            ];

            $boards[$lastIndex]['free_rects'] = $this->splitFreeRects(
                $boards[$lastIndex]['free_rects'],
                $placement['x'],
                $placement['y'],
                $placement['width'],
                $placement['height'],
            );
        }

        $normalizedBoards = array_map(function (array $board): array {
            return [
                'index' => $board['index'],
                'width_cm' => $board['width_cm'],
                'height_cm' => $board['height_cm'],
                'placements' => $board['placements'],
                // Rectángulos disjuntos (no free-rects solapados del packing).
                'remnants' => $this->computeNonOverlappingRemnants(
                    (float) $board['width_cm'],
                    (float) $board['height_cm'],
                    $board['placements'] ?? [],
                ),
            ];
        }, $boards);

        $boardArea = $boardWidthCm * $boardHeightCm;
        $totalBoardArea = $boardArea * max(1, count($normalizedBoards));
        $usedArea = array_reduce(
            $normalizedBoards,
            fn (float $carry, array $board): float => $carry + array_reduce(
                $board['placements'],
                fn (float $inner, array $placement): float => $inner + ($placement['width'] * $placement['height']),
                0.0,
            ),
            0.0,
        );

        return [
            'boards' => $normalizedBoards,
            'total_boards' => count($normalizedBoards),
            'used_area_cm2' => round($usedArea, 2),
            'waste_area_cm2' => round(max(0, $totalBoardArea - $usedArea), 2),
            'efficiency_percent' => $totalBoardArea > 0 ? round(($usedArea / $totalBoardArea) * 100, 2) : 0,
        ];
    }

    /**
     * Sobrantes como rectángulos disjuntos a partir de las piezas colocadas.
     * Evita el free-rect solapado del packing (MaxRects), que dibuja un solo
     * contorno punteado con varias etiquetas S-0N dentro.
     *
     * @param  list<array{x?: float, y?: float, width?: float, height?: float}>  $placements
     * @return list<array{x: float, y: float, width: float, height: float}>
     */
    private function computeNonOverlappingRemnants(float $boardWidthCm, float $boardHeightCm, array $placements): array
    {
        $free = [[
            'x' => 0.0,
            'y' => 0.0,
            'width' => $boardWidthCm,
            'height' => $boardHeightCm,
        ]];

        foreach ($placements as $placement) {
            $free = $this->subtractRectangle(
                $free,
                (float) ($placement['x'] ?? 0),
                (float) ($placement['y'] ?? 0),
                (float) ($placement['width'] ?? 0),
                (float) ($placement['height'] ?? 0),
            );
        }

        $free = $this->mergeAdjacentRects($free);

        return array_values(array_filter(
            $free,
            fn (array $rect): bool => $rect['width'] >= 15 && $rect['height'] >= 15,
        ));
    }

    /**
     * Diferencia de rectángulos sin solapes (hasta 4 trozos por rectángulo libre).
     *
     * @param  list<array{x: float, y: float, width: float, height: float}>  $rects
     * @return list<array{x: float, y: float, width: float, height: float}>
     */
    private function subtractRectangle(array $rects, float $x, float $y, float $width, float $height): array
    {
        if ($width <= 0 || $height <= 0) {
            return $rects;
        }

        $result = [];
        $x2 = $x + $width;
        $y2 = $y + $height;

        foreach ($rects as $rect) {
            if (! $this->rectanglesOverlap($rect, $x, $y, $width, $height)) {
                $result[] = $rect;

                continue;
            }

            $rx = (float) $rect['x'];
            $ry = (float) $rect['y'];
            $rx2 = $rx + (float) $rect['width'];
            $ry2 = $ry + (float) $rect['height'];

            // Izquierda (franja completa en Y del libre).
            if ($x > $rx + 0.05) {
                $result[] = [
                    'x' => $rx,
                    'y' => $ry,
                    'width' => $x - $rx,
                    'height' => $ry2 - $ry,
                ];
            }

            // Derecha.
            if ($x2 < $rx2 - 0.05) {
                $result[] = [
                    'x' => $x2,
                    'y' => $ry,
                    'width' => $rx2 - $x2,
                    'height' => $ry2 - $ry,
                ];
            }

            $overlapLeft = max($rx, $x);
            $overlapRight = min($rx2, $x2);
            $overlapWidth = $overlapRight - $overlapLeft;

            if ($overlapWidth > 0.05) {
                // Abajo (solo en la franja horizontal solapada).
                if ($y > $ry + 0.05) {
                    $result[] = [
                        'x' => $overlapLeft,
                        'y' => $ry,
                        'width' => $overlapWidth,
                        'height' => $y - $ry,
                    ];
                }

                // Arriba.
                if ($y2 < $ry2 - 0.05) {
                    $result[] = [
                        'x' => $overlapLeft,
                        'y' => $y2,
                        'width' => $overlapWidth,
                        'height' => $ry2 - $y2,
                    ];
                }
            }
        }

        return array_values(array_filter(
            $result,
            fn (array $rect): bool => $rect['width'] > 0.1 && $rect['height'] > 0.1,
        ));
    }

    /**
     * Une rectángulos adyacentes que forman uno solo.
     *
     * @param  list<array{x: float, y: float, width: float, height: float}>  $rects
     * @return list<array{x: float, y: float, width: float, height: float}>
     */
    private function mergeAdjacentRects(array $rects): array
    {
        $rects = array_map(
            fn (array $rect): array => [
                'x' => round((float) $rect['x'], 2),
                'y' => round((float) $rect['y'], 2),
                'width' => round((float) $rect['width'], 2),
                'height' => round((float) $rect['height'], 2),
            ],
            $rects,
        );

        $merged = true;
        while ($merged) {
            $merged = false;
            $count = count($rects);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $rects[$i];
                    $b = $rects[$j];

                    // Misma columna vertical.
                    if (abs($a['x'] - $b['x']) < 0.05 && abs($a['width'] - $b['width']) < 0.05) {
                        if (abs(($a['y'] + $a['height']) - $b['y']) < 0.05) {
                            $rects[$i] = [
                                'x' => $a['x'],
                                'y' => $a['y'],
                                'width' => $a['width'],
                                'height' => round($a['height'] + $b['height'], 2),
                            ];
                            array_splice($rects, $j, 1);
                            $merged = true;
                            break 2;
                        }

                        if (abs(($b['y'] + $b['height']) - $a['y']) < 0.05) {
                            $rects[$i] = [
                                'x' => $b['x'],
                                'y' => $b['y'],
                                'width' => $b['width'],
                                'height' => round($b['height'] + $a['height'], 2),
                            ];
                            array_splice($rects, $j, 1);
                            $merged = true;
                            break 2;
                        }
                    }

                    // Misma fila horizontal.
                    if (abs($a['y'] - $b['y']) < 0.05 && abs($a['height'] - $b['height']) < 0.05) {
                        if (abs(($a['x'] + $a['width']) - $b['x']) < 0.05) {
                            $rects[$i] = [
                                'x' => $a['x'],
                                'y' => $a['y'],
                                'width' => round($a['width'] + $b['width'], 2),
                                'height' => $a['height'],
                            ];
                            array_splice($rects, $j, 1);
                            $merged = true;
                            break 2;
                        }

                        if (abs(($b['x'] + $b['width']) - $a['x']) < 0.05) {
                            $rects[$i] = [
                                'x' => $b['x'],
                                'y' => $b['y'],
                                'width' => round($b['width'] + $a['width'], 2),
                                'height' => $b['height'],
                            ];
                            array_splice($rects, $j, 1);
                            $merged = true;
                            break 2;
                        }
                    }
                }
            }
        }

        usort($rects, fn (array $a, array $b): int => ($a['y'] <=> $b['y']) ?: ($a['x'] <=> $b['x']));

        return array_values($rects);
    }

    /**
     * @param  list<array{label: string, width_cm: float, height_cm: float, quantity: int}>  $pieces
     * @return list<array{label: string, width_cm: float, height_cm: float}>
     */
    private function expandPieces(array $pieces): array
    {
        $expanded = [];

        foreach ($pieces as $piece) {
            for ($i = 1; $i <= $piece['quantity']; $i++) {
                $expanded[] = [
                    'label' => $piece['quantity'] > 1 ? "{$piece['label']} #{$i}" : $piece['label'],
                    'width_cm' => (float) $piece['width_cm'],
                    'height_cm' => (float) $piece['height_cm'],
                ];
            }
        }

        return $expanded;
    }

    /**
     * @param  list<array{x: float, y: float, width: float, height: float}>  $freeRects
     * @return array{x: float, y: float, width: float, height: float, rotated: bool}|null
     */
    private function findPlacement(array $freeRects, float $pieceWidth, float $pieceHeight): ?array
    {
        foreach ($freeRects as $rect) {
            if ($pieceWidth <= $rect['width'] && $pieceHeight <= $rect['height']) {
                return [
                    'x' => $rect['x'],
                    'y' => $rect['y'],
                    'width' => $pieceWidth,
                    'height' => $pieceHeight,
                    'rotated' => false,
                ];
            }

            if ($pieceHeight <= $rect['width'] && $pieceWidth <= $rect['height']) {
                return [
                    'x' => $rect['x'],
                    'y' => $rect['y'],
                    'width' => $pieceHeight,
                    'height' => $pieceWidth,
                    'rotated' => true,
                ];
            }
        }

        return null;
    }

    /**
     * @param  list<array{x: float, y: float, width: float, height: float}>  $freeRects
     * @return list<array{x: float, y: float, width: float, height: float}>
     */
    private function splitFreeRects(array $freeRects, float $x, float $y, float $width, float $height): array
    {
        $newRects = [];

        foreach ($freeRects as $rect) {
            if (! $this->rectanglesOverlap($rect, $x, $y, $width, $height)) {
                $newRects[] = $rect;

                continue;
            }

            if ($x > $rect['x']) {
                $newRects[] = [
                    'x' => $rect['x'],
                    'y' => $rect['y'],
                    'width' => $x - $rect['x'],
                    'height' => $rect['height'],
                ];
            }

            if (($x + $width) < ($rect['x'] + $rect['width'])) {
                $newRects[] = [
                    'x' => $x + $width,
                    'y' => $rect['y'],
                    'width' => ($rect['x'] + $rect['width']) - ($x + $width),
                    'height' => $rect['height'],
                ];
            }

            if ($y > $rect['y']) {
                $newRects[] = [
                    'x' => $rect['x'],
                    'y' => $rect['y'],
                    'width' => $rect['width'],
                    'height' => $y - $rect['y'],
                ];
            }

            if (($y + $height) < ($rect['y'] + $rect['height'])) {
                $newRects[] = [
                    'x' => $rect['x'],
                    'y' => $y + $height,
                    'width' => $rect['width'],
                    'height' => ($rect['y'] + $rect['height']) - ($y + $height),
                ];
            }
        }

        return $this->pruneFreeRects($newRects);
    }

    /**
     * @param  array{x: float, y: float, width: float, height: float}  $rect
     */
    private function rectanglesOverlap(array $rect, float $x, float $y, float $width, float $height): bool
    {
        return ! ($x >= $rect['x'] + $rect['width']
            || $x + $width <= $rect['x']
            || $y >= $rect['y'] + $rect['height']
            || $y + $height <= $rect['y']);
    }

    /**
     * @param  list<array{x: float, y: float, width: float, height: float}>  $rects
     * @return list<array{x: float, y: float, width: float, height: float}>
     */
    private function pruneFreeRects(array $rects): array
    {
        $rects = array_values(array_filter($rects, fn (array $rect): bool => $rect['width'] > 0.1 && $rect['height'] > 0.1));

        usort($rects, fn (array $a, array $b): int => ($a['y'] <=> $b['y']) ?: ($a['x'] <=> $b['x']));

        $pruned = [];

        foreach ($rects as $rect) {
            $isContained = false;

            foreach ($pruned as $kept) {
                if ($rect['x'] >= $kept['x']
                    && $rect['y'] >= $kept['y']
                    && ($rect['x'] + $rect['width']) <= ($kept['x'] + $kept['width'])
                    && ($rect['y'] + $rect['height']) <= ($kept['y'] + $kept['height'])) {
                    $isContained = true;
                    break;
                }
            }

            if (! $isContained) {
                $pruned[] = $rect;
            }
        }

        return $pruned;
    }
}
