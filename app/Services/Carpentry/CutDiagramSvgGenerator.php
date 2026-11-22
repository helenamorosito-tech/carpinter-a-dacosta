<?php

namespace App\Services\Carpentry;

use App\Support\MeasureFormat;

class CutDiagramSvgGenerator
{
    private const COLORS = [
        '#F59E0B', '#3B82F6', '#10B981', '#EF4444', '#8B5CF6',
        '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16',
    ];

    /**
     * @param  array{
     *     boards: list<array{
     *         index: int,
     *         width_cm: float,
     *         height_cm: float,
     *         placements: list<array{label: string, x: float, y: float, width: float, height: float, rotated: bool}>,
     *         remnants: list<array{x: float, y: float, width: float, height: float}>
     *     }>
     * }  $cutPlan
     */
    public function generate(array $cutPlan, float $scalePxPerCm = 2.0): string
    {
        $boards = $cutPlan['boards'] ?? [];

        if ($boards === []) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="80"><text x="20" y="40" font-size="16">Sin plan de corte</text></svg>';
        }

        $padding = 40;
        $gap = 30;
        $calloutHeight = 72;
        $titleSpace = 24;
        $maxBoardWidth = max(array_column($boards, 'width_cm'));
        $svgWidth = (int) (($maxBoardWidth * $scalePxPerCm) + ($padding * 2));
        $currentY = $padding;
        $elements = [];
        $colorIndex = 0;

        foreach ($boards as $board) {
            $boardWidthPx = $board['width_cm'] * $scalePxPerCm;
            $boardHeightPx = $board['height_cm'] * $scalePxPerCm;
            $originX = $padding;
            $originY = $currentY + $titleSpace + $calloutHeight;
            $callouts = [];

            $boardTitle = $board['title'] ?? sprintf('Tablero %d', $board['index']);
            $elements[] = sprintf(
                '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="14" font-weight="bold" fill="#111827">%s — %s × %s cm</text>',
                $originX,
                (int) ($currentY + 14),
                htmlspecialchars($boardTitle, ENT_QUOTES),
                MeasureFormat::numberDot($board['width_cm']),
                MeasureFormat::numberDot($board['height_cm']),
            );

            $elements[] = sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="#ffffff" stroke="#111827" stroke-width="2"/>',
                $originX,
                (int) $originY,
                (int) $boardWidthPx,
                (int) $boardHeightPx,
            );

            foreach ($board['placements'] as $placement) {
                $color = self::COLORS[$colorIndex % count(self::COLORS)];
                $colorIndex++;

                $x = $originX + ($placement['x'] * $scalePxPerCm);
                $y = $originY + ($placement['y'] * $scalePxPerCm);
                $w = $placement['width'] * $scalePxPerCm;
                $h = $placement['height'] * $scalePxPerCm;

                $elements[] = sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" fill-opacity="0.75" stroke="#111827" stroke-width="1.5"/>',
                    (int) $x,
                    (int) $y,
                    (int) $w,
                    (int) $h,
                    $color,
                );

                $label = htmlspecialchars((string) $placement['label'], ENT_QUOTES);
                $sizeLabel = MeasureFormat::pairDot($placement['width'], $placement['height'], ' x ').' cm';
                $elements = array_merge(
                    $elements,
                    $this->pieceLabelElements($label, $sizeLabel, $x, $y, $w, $h, 13, 18, $callouts),
                );
            }

            foreach ($board['remnants'] as $remnant) {
                $x = $originX + ($remnant['x'] * $scalePxPerCm);
                $y = $originY + ($remnant['y'] * $scalePxPerCm);
                $w = $remnant['width'] * $scalePxPerCm;
                $h = $remnant['height'] * $scalePxPerCm;

                $elements[] = sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" fill="#f3f4f6" stroke="#4b5563" stroke-dasharray="5 3" stroke-width="1.5"/>',
                    (int) $x,
                    (int) $y,
                    (int) $w,
                    (int) $h,
                );

                $code = htmlspecialchars((string) ($remnant['label'] ?? 'Sobrante'), ENT_QUOTES);
                $sizeLabel = MeasureFormat::pairDot($remnant['width'], $remnant['height'], ' x ').' cm';
                $elements = array_merge(
                    $elements,
                    $this->pieceLabelElements($code, $sizeLabel, $x, $y, $w, $h, 10, 13, $callouts, true),
                );
            }

            $elements = array_merge(
                $elements,
                $this->calloutElements(
                    $callouts,
                    (int) $originX,
                    (int) ($originX + $boardWidthPx),
                    (int) ($originY - $calloutHeight),
                    (int) $originY,
                ),
            );

            $currentY = $originY + $boardHeightPx + $gap;
        }

        $svgHeight = (int) ($currentY + $padding);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">%s</svg>',
            $svgWidth,
            $svgHeight,
            $svgWidth,
            $svgHeight,
            implode('', $elements),
        );
    }

    /**
     * @param  list<array{label: string, size: string, cx: int, cy: int, label_size: float, measure_size: float}>  $callouts
     * @return list<string>
     */
    protected function pieceLabelElements(
        string $label,
        string $sizeLabel,
        float $x,
        float $y,
        float $w,
        float $h,
        float $labelSize = 13,
        float $measureSize = 18,
        array &$callouts = [],
        bool $isRemnant = false,
    ): array {
        if ($w < 8 || $h < 8) {
            return [];
        }

        $cx = (int) ($x + ($w / 2));
        $cy = (int) ($y + ($h / 2));

        $needW = max(strlen(strip_tags($sizeLabel)) * $measureSize * 0.55, strlen(strip_tags($label)) * $labelSize * 0.55) + 14;
        $needH = $labelSize + $measureSize + 20;
        $fitsHorizontal = $w >= ($needW * 1.2) && $h >= ($needH * 1.25);
        $fitsVertical = $w >= (($labelSize + $measureSize + 14) * 1.15) && $h >= ($needW * 1.15);
        $forceExternal = $isRemnant && (min($w, $h) < 100 || max($w, $h) < 140);

        if ($forceExternal || (! $fitsHorizontal && ! $fitsVertical)) {
            $callouts[] = [
                'label' => $label,
                'size' => $sizeLabel,
                'cx' => $cx,
                'cy' => $cy,
                'label_size' => $labelSize,
                'measure_size' => $measureSize,
            ];

            return [];
        }

        $vertical = $h >= ($w * 1.25) || ($w < 48 && $h > $w);
        $lineGap = (int) max(8, round($measureSize * 0.7));
        $elements = [];

        if ($vertical) {
            if ($w >= 22 && $h >= 60) {
                $elements[] = sprintf(
                    '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="%s" font-weight="bold" text-anchor="middle" dominant-baseline="middle" fill="#000000" transform="rotate(-90 %d %d)">%s</text>',
                    $cx - 8,
                    $cy,
                    $labelSize,
                    $cx - 8,
                    $cy,
                    $label,
                );
                $elements[] = sprintf(
                    '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="%s" font-weight="bold" text-anchor="middle" dominant-baseline="middle" fill="#000000" transform="rotate(-90 %d %d)">%s</text>',
                    $cx + 9,
                    $cy,
                    $measureSize,
                    $cx + 9,
                    $cy,
                    $sizeLabel,
                );
            } else {
                $elements[] = sprintf(
                    '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="%s" font-weight="bold" text-anchor="middle" dominant-baseline="middle" fill="#000000" transform="rotate(-90 %d %d)">%s</text>',
                    $cx,
                    $cy,
                    min($measureSize, 12),
                    $cx,
                    $cy,
                    $sizeLabel,
                );
            }

            return $elements;
        }

        if ($w >= 70 && $h >= 34) {
            $elements[] = sprintf(
                '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="%s" font-weight="bold" text-anchor="middle" dominant-baseline="middle" fill="#000000">%s</text>',
                $cx,
                (int) ($cy - $lineGap),
                $labelSize,
                $label,
            );
            $elements[] = sprintf(
                '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="%s" font-weight="bold" text-anchor="middle" dominant-baseline="middle" fill="#000000">%s</text>',
                $cx,
                (int) ($cy + $lineGap),
                $measureSize,
                $sizeLabel,
            );

            return $elements;
        }

        $elements[] = sprintf(
            '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="%s" font-weight="bold" text-anchor="middle" dominant-baseline="middle" fill="#000000">%s</text>',
            $cx,
            $cy,
            min($measureSize, 12),
            $sizeLabel,
        );

        return $elements;
    }

    /**
     * @param  list<array{label: string, size: string, cx: int, cy: int, label_size: float, measure_size: float}>  $callouts
     * @return list<string>
     */
    protected function calloutElements(
        array $callouts,
        int $boardLeft,
        int $boardRight,
        int $bandTop,
        int $boardTop,
    ): array {
        if ($callouts === []) {
            return [];
        }

        usort(
            $callouts,
            fn (array $a, array $b): int => ($a['cy'] <=> $b['cy']) ?: ($a['cx'] <=> $b['cx']),
        );

        $elements = [];
        $y = $bandTop + 12;
        $number = 1;
        $lineHeight = 17;

        foreach ($callouts as $callout) {
            $elements[] = sprintf(
                '<circle cx="%d" cy="%d" r="8" fill="#111827"/>',
                $callout['cx'],
                $callout['cy'],
            );
            $elements[] = sprintf(
                '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="bold" text-anchor="middle" dominant-baseline="middle" fill="#ffffff">%d</text>',
                $callout['cx'],
                $callout['cy'],
                $number,
            );
            $elements[] = sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#374151" stroke-width="1"/>',
                $callout['cx'],
                $callout['cy'] - 8,
                $callout['cx'],
                $boardTop - 1,
            );
            $elements[] = sprintf(
                '<text x="%d" y="%d" font-family="Arial, Helvetica, sans-serif" font-size="10" font-weight="bold" fill="#000000">%s</text>',
                $boardLeft,
                $y,
                htmlspecialchars(sprintf('%d. %s — %s', $number, $callout['label'], $callout['size']), ENT_QUOTES),
            );

            $y += $lineHeight;
            $number++;
        }

        return $elements;
    }
}
