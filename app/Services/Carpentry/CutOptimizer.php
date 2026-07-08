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
            $remnants = array_values(array_filter(
                $board['free_rects'],
                fn (array $rect): bool => $rect['width'] >= 5 && $rect['height'] >= 5,
            ));

            return [
                'index' => $board['index'],
                'width_cm' => $board['width_cm'],
                'height_cm' => $board['height_cm'],
                'placements' => $board['placements'],
                'remnants' => $remnants,
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
