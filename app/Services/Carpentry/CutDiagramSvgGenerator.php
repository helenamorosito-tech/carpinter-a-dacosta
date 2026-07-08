<?php

namespace App\Services\Carpentry;

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
        $maxBoardWidth = max(array_column($boards, 'width_cm'));
        $svgWidth = (int) (($maxBoardWidth * $scalePxPerCm) + ($padding * 2));
        $currentY = $padding;
        $elements = [];
        $colorIndex = 0;

        foreach ($boards as $board) {
            $boardWidthPx = $board['width_cm'] * $scalePxPerCm;
            $boardHeightPx = $board['height_cm'] * $scalePxPerCm;
            $originX = $padding;
            $originY = $currentY;

            $elements[] = sprintf(
                '<text x="%d" y="%d" font-size="14" font-weight="bold" fill="#1f2937">Tablero %d — %s × %s cm</text>',
                $originX,
                (int) ($originY - 12),
                $board['index'],
                number_format($board['width_cm'], 2, '.', ''),
                number_format($board['height_cm'], 2, '.', ''),
            );

            $elements[] = sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="#f3f4f6" stroke="#374151" stroke-width="2"/>',
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
                    '<rect x="%d" y="%d" width="%d" height="%d" fill="%s" fill-opacity="0.75" stroke="#111827" stroke-width="1"/>',
                    (int) $x,
                    (int) $y,
                    (int) $w,
                    (int) $h,
                    $color,
                );

                $label = htmlspecialchars($placement['label'], ENT_QUOTES);
                $sizeLabel = number_format($placement['width'], 1).'×'.number_format($placement['height'], 1);

                $elements[] = sprintf(
                    '<text x="%d" y="%d" font-size="11" text-anchor="middle" fill="#111827">%s</text>',
                    (int) ($x + ($w / 2)),
                    (int) ($y + ($h / 2) - 4),
                    $label,
                );

                $elements[] = sprintf(
                    '<text x="%d" y="%d" font-size="10" text-anchor="middle" fill="#374151">%s cm</text>',
                    (int) ($x + ($w / 2)),
                    (int) ($y + ($h / 2) + 10),
                    $sizeLabel,
                );
            }

            foreach ($board['remnants'] as $remnant) {
                $x = $originX + ($remnant['x'] * $scalePxPerCm);
                $y = $originY + ($remnant['y'] * $scalePxPerCm);
                $w = $remnant['width'] * $scalePxPerCm;
                $h = $remnant['height'] * $scalePxPerCm;

                $elements[] = sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" fill="#d1d5db" fill-opacity="0.5" stroke="#6b7280" stroke-dasharray="4 2" stroke-width="1"/>',
                    (int) $x,
                    (int) $y,
                    (int) $w,
                    (int) $h,
                );

                if ($w > 40 && $h > 20) {
                    $elements[] = sprintf(
                        '<text x="%d" y="%d" font-size="10" text-anchor="middle" fill="#6b7280">Remanente %s×%s</text>',
                        (int) ($x + ($w / 2)),
                        (int) ($y + ($h / 2)),
                        number_format($remnant['width'], 1),
                        number_format($remnant['height'], 1),
                    );
                }
            }

            $currentY += $boardHeightPx + $gap + 20;
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
}
