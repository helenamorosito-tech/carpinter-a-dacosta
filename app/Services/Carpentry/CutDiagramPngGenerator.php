<?php

namespace App\Services\Carpentry;

use App\Support\MeasureFormat;

class CutDiagramPngGenerator
{
    private const COLORS = [
        [245, 158, 11],
        [59, 130, 246],
        [16, 185, 129],
        [239, 68, 68],
        [139, 92, 246],
        [236, 72, 153],
        [20, 184, 166],
        [249, 115, 22],
        [99, 102, 241],
        [132, 204, 22],
    ];

    /** Tamaño de la etiqueta de pieza (pt). */
    private const LABEL_FONT_SIZE = 13;

    /** Tamaño de las medidas dentro del dibujo (pt). */
    private const MEASURE_FONT_SIZE = 18;

    /** Etiqueta de sobrante (pt). */
    private const REMNANT_LABEL_FONT_SIZE = 16;

    /** Medida de sobrante (pt). */
    private const REMNANT_MEASURE_FONT_SIZE = 14;

    /** Número de sobrante en el marcador del dibujo (pt). */
    private const REMNANT_MARKER_FONT_SIZE = 18;

    /** Margen superior del tablero (ya no hay leyenda de piezas encima). */
    private const CALLOUT_HEIGHT = 12;

    private ?string $fontBoldPath = null;

    private ?string $fontRegularPath = null;

    private bool $fontResolved = false;

    /**
     * @param  array{
     *     boards: list<array{
     *         index: int,
     *         width_cm: float,
     *         height_cm: float,
     *         placements: list<array{label: string, x: float, y: float, width: float, height: float, rotated: bool}>,
     *         remnants: list<array{x: float, y: float, width: float, height: float, label?: string}>
     *     }>
     * }  $cutPlan
     */
    public function generateDataUri(array $cutPlan, float $scalePxPerCm = 2.5): string
    {
        $png = $this->generateBinary($cutPlan, $scalePxPerCm);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Escala automática para que un tablero quepa completo en una página A4 del PDF.
     * Se dibuja en orientación horizontal para facilitar la lectura de textos.
     *
     * @param  array{
     *     index: int,
     *     width_cm: float,
     *     height_cm: float,
     *     placements: list<array{label: string, x: float, y: float, width: float, height: float, rotated: bool}>,
     *     remnants: list<array{x: float, y: float, width: float, height: float, label?: string}>
     * }  $board
     */
    public function generateBoardDataUriForPdf(array $board, int $maxWidthPx = 720, int $maxHeightPx = 340): string
    {
        $board = $this->orientBoardLandscape($board);

        $padding = 28;
        $titleSpace = 0;
        $calloutSpace = self::CALLOUT_HEIGHT;
        $boardWidthCm = max(1.0, (float) ($board['width_cm'] ?? 1));
        $boardHeightCm = max(1.0, (float) ($board['height_cm'] ?? 1));

        $scaleByWidth = ($maxWidthPx - ($padding * 2)) / $boardWidthCm;
        $scaleByHeight = ($maxHeightPx - $padding - $titleSpace - $calloutSpace) / $boardHeightCm;
        $scale = max(0.5, min($scaleByWidth, $scaleByHeight, 2.2));

        return $this->generateDataUri(['boards' => [$board]], $scale);
    }

    /**
     * Gira el tablero a vista horizontal (lado largo en X) para leer mejor las etiquetas.
     *
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    protected function orientBoardLandscape(array $board): array
    {
        $width = (float) ($board['width_cm'] ?? 0);
        $height = (float) ($board['height_cm'] ?? 0);

        if ($width <= 0 || $height <= 0 || $width >= $height) {
            return $board;
        }

        $board['width_cm'] = $height;
        $board['height_cm'] = $width;
        $board['display_orientation'] = 'landscape';

        $board['placements'] = array_map(
            function (array $placement) use ($width): array {
                $x = (float) ($placement['x'] ?? 0);
                $y = (float) ($placement['y'] ?? 0);
                $w = (float) ($placement['width'] ?? 0);
                $h = (float) ($placement['height'] ?? 0);

                // Rotación 90° antihoraria: el tablero alto pasa a apaisado.
                return [
                    ...$placement,
                    'x' => round($y, 2),
                    'y' => round($width - $x - $w, 2),
                    'width' => round($h, 2),
                    'height' => round($w, 2),
                    'rotated' => ! ((bool) ($placement['rotated'] ?? false)),
                ];
            },
            $board['placements'] ?? [],
        );

        $board['remnants'] = array_map(
            function (array $remnant) use ($width): array {
                $x = (float) ($remnant['x'] ?? 0);
                $y = (float) ($remnant['y'] ?? 0);
                $w = (float) ($remnant['width'] ?? 0);
                $h = (float) ($remnant['height'] ?? 0);

                return [
                    ...$remnant,
                    'x' => round($y, 2),
                    'y' => round($width - $x - $w, 2),
                    'width' => round($h, 2),
                    'height' => round($w, 2),
                ];
            },
            $board['remnants'] ?? [],
        );

        return $board;
    }

    /**
     * @param  array{
     *     boards: list<array{
     *         index: int,
     *         width_cm: float,
     *         height_cm: float,
     *         placements: list<array{label: string, x: float, y: float, width: float, height: float, rotated: bool}>,
     *         remnants: list<array{x: float, y: float, width: float, height: float, label?: string}>
     *     }>
     * }  $cutPlan
     */
    public function generateBinary(array $cutPlan, float $scalePxPerCm = 2.5): string
    {
        $boards = $cutPlan['boards'] ?? [];

        if ($boards === []) {
            return $this->emptyImage('Sin plan de corte');
        }

        $padding = 48;
        $gap = 36;
        $titleSpace = 0;
        $calloutSpace = self::CALLOUT_HEIGHT;
        $maxBoardWidth = max(array_column($boards, 'width_cm'));
        $imageWidth = (int) max(400, ($maxBoardWidth * $scalePxPerCm) + ($padding * 2));

        $totalHeight = $padding;
        foreach ($boards as $board) {
            $totalHeight += $titleSpace + $calloutSpace + ((float) $board['height_cm'] * $scalePxPerCm) + $gap;
        }
        $totalHeight += $padding;

        $image = imagecreatetruecolor($imageWidth, (int) $totalHeight);
        if ($image === false) {
            return $this->emptyImage('Error al generar diagrama');
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        $white = imagecolorallocate($image, 255, 255, 255);
        $boardBg = imagecolorallocate($image, 255, 255, 255);
        $boardBorder = imagecolorallocate($image, 17, 24, 39);
        $pieceBorder = imagecolorallocate($image, 17, 24, 39);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $remnantFill = imagecolorallocate($image, 243, 244, 246);
        $remnantBorder = imagecolorallocate($image, 75, 85, 99);
        $remnantText = imagecolorallocate($image, 220, 38, 38);

        imagefilledrectangle($image, 0, 0, $imageWidth, (int) $totalHeight, $white);

        $currentY = $padding;
        $colorIndex = 0;

        foreach ($boards as $board) {
            $boardWidthPx = (float) $board['width_cm'] * $scalePxPerCm;
            $boardHeightPx = (float) $board['height_cm'] * $scalePxPerCm;
            $originX = $padding;
            $originY = $currentY + $titleSpace + $calloutSpace;

            imagefilledrectangle(
                $image,
                (int) $originX,
                (int) $originY,
                (int) ($originX + $boardWidthPx),
                (int) ($originY + $boardHeightPx),
                $boardBg,
            );
            imagerectangle(
                $image,
                (int) $originX,
                (int) $originY,
                (int) ($originX + $boardWidthPx),
                (int) ($originY + $boardHeightPx),
                $boardBorder,
            );

            foreach ($board['remnants'] ?? [] as $remnant) {
                $x = $originX + ((float) $remnant['x'] * $scalePxPerCm);
                $y = $originY + ((float) $remnant['y'] * $scalePxPerCm);
                $w = (float) $remnant['width'] * $scalePxPerCm;
                $h = (float) $remnant['height'] * $scalePxPerCm;

                imagefilledrectangle($image, (int) $x, (int) $y, (int) ($x + $w), (int) ($y + $h), $remnantFill);
                $this->drawDashedRectangle($image, (int) $x, (int) $y, (int) ($x + $w), (int) ($y + $h), $remnantBorder);
            }

            $callouts = [];

            foreach ($board['placements'] ?? [] as $placement) {
                $rgb = self::COLORS[$colorIndex % count(self::COLORS)];
                $colorIndex++;
                $fill = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], 40);

                $x = $originX + ((float) $placement['x'] * $scalePxPerCm);
                $y = $originY + ((float) $placement['y'] * $scalePxPerCm);
                $w = (float) $placement['width'] * $scalePxPerCm;
                $h = (float) $placement['height'] * $scalePxPerCm;

                imagefilledrectangle($image, (int) $x, (int) $y, (int) ($x + $w), (int) ($y + $h), $fill);
                imagerectangle($image, (int) $x, (int) $y, (int) ($x + $w), (int) ($y + $h), $pieceBorder);

                $label = (string) ($placement['label'] ?? 'Pieza');
                $sizeLabel = MeasureFormat::pairDot($placement['width'], $placement['height'], ' x ').' cm';
                $this->drawPieceLabels(
                    $image,
                    $label,
                    $sizeLabel,
                    (int) $x,
                    (int) $y,
                    (int) $w,
                    (int) $h,
                    $textColor,
                    self::LABEL_FONT_SIZE,
                    self::MEASURE_FONT_SIZE,
                    $callouts,
                    false,
                );
            }

            foreach ($board['remnants'] ?? [] as $remnantIndex => $remnant) {
                $x = $originX + ((float) $remnant['x'] * $scalePxPerCm);
                $y = $originY + ((float) $remnant['y'] * $scalePxPerCm);
                $w = (float) $remnant['width'] * $scalePxPerCm;
                $h = (float) $remnant['height'] * $scalePxPerCm;

                $code = (string) ($remnant['label'] ?? 'Sobrante');
                $sizeLabel = MeasureFormat::pairDot($remnant['width'], $remnant['height'], ' x ').' cm';
                $this->drawPieceLabels(
                    $image,
                    $code,
                    $sizeLabel,
                    (int) $x,
                    (int) $y,
                    (int) $w,
                    (int) $h,
                    $remnantText,
                    self::REMNANT_LABEL_FONT_SIZE,
                    self::REMNANT_MEASURE_FONT_SIZE,
                    $callouts,
                    true,
                    $remnantIndex + 1,
                );
            }

            $this->drawCallouts(
                $image,
                $callouts,
                (int) $originX,
                (int) ($originX + $boardWidthPx),
                (int) ($originY - self::CALLOUT_HEIGHT),
                (int) $originY,
                $textColor,
            );

            $currentY = $originY + $boardHeightPx + $gap;
        }

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary !== '' ? $binary : $this->emptyImage('Error al generar diagrama');
    }

    /**
     * Etiqueta + medida centradas dentro de la pieza.
     * Las piezas nunca usan marcador numérico (evita confusión con sobrantes 1, 2, 3).
     *
     * @param  \GdImage|resource  $image
     * @param  list<array{label: string, size: string, cx: int, cy: int, label_size: float, measure_size: float, is_remnant?: bool, marker_number?: int|null}>  $callouts
     */
    protected function drawPieceLabels(
        $image,
        string $label,
        string $sizeLabel,
        int $x,
        int $y,
        int $w,
        int $h,
        int $color,
        float $labelSize = self::LABEL_FONT_SIZE,
        float $measureSize = self::MEASURE_FONT_SIZE,
        array &$callouts = [],
        bool $isRemnant = false,
        ?int $markerNumber = null,
    ): void {
        if ($w < 8 || $h < 8) {
            return;
        }

        $cx = (int) ($x + ($w / 2));
        $cy = (int) ($y + ($h / 2));

        if ($isRemnant) {
            $callouts[] = [
                'label' => $label,
                'size' => $sizeLabel,
                'cx' => $cx,
                'cy' => $cy,
                'label_size' => $labelSize,
                'measure_size' => $measureSize,
                'is_remnant' => true,
                'marker_number' => $markerNumber,
            ];

            return;
        }

        $displayLabel = $this->shortPieceLabel($label);
        [$labelSize, $measureSize] = $this->bestPieceFontSizes(
            $w,
            $h,
            $displayLabel,
            $sizeLabel,
            $labelSize,
            $measureSize,
        );

        $vertical = $h >= ($w * 1.15) || ($w < 56 && $h > $w);
        $lineGap = (int) max(7, round($measureSize * 0.65));

        if ($vertical) {
            if ($w >= 20 && $h >= 48) {
                $this->drawCenteredTextVertical($image, $displayLabel, $cx - 7, $cy, $color, $labelSize, true);
                $this->drawCenteredTextVertical($image, $sizeLabel, $cx + 8, $cy, $color, $measureSize, true);
            } else {
                $this->drawCenteredTextVertical($image, $sizeLabel, $cx, $cy, $color, min($measureSize, 11), true);
            }

            return;
        }

        if ($w >= 56 && $h >= 28) {
            $this->drawCenteredText($image, $displayLabel, $cx, $cy - $lineGap, $color, $labelSize, true);
            $this->drawCenteredText($image, $sizeLabel, $cx, $cy + $lineGap, $color, $measureSize, true);

            return;
        }

        $this->drawCenteredText($image, $sizeLabel, $cx, $cy, $color, min($measureSize, 11), true);
    }

    /**
     * Acorta nombres largos (p. ej. quita " (16 mm)") para que quepan en la pieza.
     */
    protected function shortPieceLabel(string $label): string
    {
        $short = preg_replace('/\s*\(\d+\s*mm\)\s*/iu', '', $label) ?? $label;
        $short = trim(preg_replace('/\s+/u', ' ', $short) ?? $short);

        if (mb_strlen($short) > 24) {
            return rtrim(mb_substr($short, 0, 22)).'…';
        }

        return $short;
    }

    /**
     * @return array{0: float, 1: float}
     */
    protected function bestPieceFontSizes(
        int $w,
        int $h,
        string $label,
        string $sizeLabel,
        float $labelSize,
        float $measureSize,
    ): array {
        $candidates = [
            [$labelSize, $measureSize],
            [11.0, 14.0],
            [10.0, 12.0],
            [9.0, 11.0],
            [8.0, 10.0],
        ];

        foreach ($candidates as [$ls, $ms]) {
            if ($this->labelFitsInside($w, $h, $label, $sizeLabel, $ls, $ms)) {
                return [$ls, $ms];
            }
        }

        return [8.0, 10.0];
    }

    protected function shouldUseExternalLabel(
        int $w,
        int $h,
        string $label,
        string $sizeLabel,
        float $labelSize,
        float $measureSize,
        bool $isRemnant,
    ): bool {
        // Solo sobrantes usan marcador numérico externo.
        return $isRemnant;
    }

    /**
     * ¿Cabe título + medida dentro del rectángulo con margen cómodo?
     */
    protected function labelFitsInside(
        int $w,
        int $h,
        string $label,
        string $sizeLabel,
        float $labelSize,
        float $measureSize,
    ): bool {
        $font = $this->resolveFont(true);
        if ($font === null || ! function_exists('imagettfbbox')) {
            return $w >= 90 && $h >= 40;
        }

        $measureBox = imagettfbbox($measureSize, 0, $font, $sizeLabel);
        $labelBox = imagettfbbox($labelSize, 0, $font, $label);
        if ($measureBox === false || $labelBox === false) {
            return false;
        }

        $measureW = abs($measureBox[2] - $measureBox[0]);
        $measureH = abs($measureBox[7] - $measureBox[1]);
        $labelW = abs($labelBox[2] - $labelBox[0]);
        $labelH = abs($labelBox[7] - $labelBox[1]);

        $pad = 14;
        $needW = max($measureW, $labelW) + $pad;
        $needH = $labelH + $measureH + $pad + 6;

        // Horizontal: margen cómodo para no invadir vecinos.
        if ($w >= (int) ($needW * 1.2) && $h >= (int) ($needH * 1.25)) {
            return true;
        }

        // Vertical: el texto corre a lo largo de la altura (más permisivo).
        $needVerticalW = $labelH + $measureH + max(8, (int) ($pad * 0.6));
        $needVerticalH = max($measureW, $labelW) + max(8, (int) ($pad * 0.6));
        if ($w >= $needVerticalW && $h >= (int) ($needVerticalH * 0.95)) {
            return true;
        }

        return false;
    }

    /**
     * Dibuja números de sobrante en rojo sobre el gráfico.
     *
     * @param  \GdImage|resource  $image
     * @param  list<array{label: string, size: string, cx: int, cy: int, label_size: float, measure_size: float, is_remnant?: bool, marker_number?: int|null}>  $callouts
     */
    protected function drawCallouts(
        $image,
        array $callouts,
        int $boardLeft,
        int $boardRight,
        int $bandTop,
        int $boardTop,
        int $color,
    ): void {
        if ($callouts === []) {
            return;
        }

        $remnantNumber = imagecolorallocate($image, 220, 38, 38);

        foreach ($callouts as $callout) {
            if (! (bool) ($callout['is_remnant'] ?? false)) {
                continue;
            }

            $number = (int) ($callout['marker_number'] ?? 0);
            if ($number < 1) {
                continue;
            }

            $this->drawCenteredText(
                $image,
                (string) $number,
                $callout['cx'],
                $callout['cy'],
                $remnantNumber,
                self::REMNANT_MARKER_FONT_SIZE,
                true,
            );
        }
    }

    protected function truncateToWidth(string $text, float $sizePt, int $maxWidthPx): string
    {
        $font = $this->resolveFont(true);
        if ($font === null || ! function_exists('imagettfbbox') || $maxWidthPx < 20) {
            return mb_strlen($text) > 48 ? mb_substr($text, 0, 45).'…' : $text;
        }

        $box = imagettfbbox($sizePt, 0, $font, $text);
        if ($box !== false && abs($box[2] - $box[0]) <= $maxWidthPx) {
            return $text;
        }

        $truncated = $text;
        while (mb_strlen($truncated) > 4) {
            $truncated = mb_substr($truncated, 0, -1);
            $candidate = rtrim($truncated).'…';
            $box = imagettfbbox($sizePt, 0, $font, $candidate);
            if ($box !== false && abs($box[2] - $box[0]) <= $maxWidthPx) {
                return $candidate;
            }
        }

        return $text;
    }

    protected function emptyImage(string $message): string
    {
        $image = imagecreatetruecolor(480, 80);
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = imagecolorallocate($image, 107, 114, 128);
        imagefilledrectangle($image, 0, 0, 480, 80, $white);
        $this->drawText($image, $message, 20, 45, $text, self::LABEL_FONT_SIZE, false);

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    /**
     * @param  \GdImage|resource  $image
     */
    protected function drawCenteredText($image, string $text, int $centerX, int $centerY, int $color, float $sizePt, bool $bold = true): void
    {
        $font = $this->resolveFont($bold);
        if ($font === null || ! function_exists('imagettftext')) {
            $fallback = $bold ? 5 : 3;
            $latin = $this->latin1($text);
            $width = imagefontwidth($fallback) * strlen($latin);
            $height = imagefontheight($fallback);
            imagestring($image, $fallback, (int) ($centerX - ($width / 2)), (int) ($centerY - ($height / 2)), $latin, $color);

            return;
        }

        [$x, $y] = $this->ttfBaselineForCenter($sizePt, 0, $font, $text, $centerX, $centerY);
        $this->drawTtf($image, $sizePt, 0, $x, $y, $color, $font, $text);
    }

    /**
     * Texto vertical (rotado 90°) centrado en la pieza, para paneles altos y estrechos.
     *
     * @param  \GdImage|resource  $image
     */
    protected function drawCenteredTextVertical($image, string $text, int $centerX, int $centerY, int $color, float $sizePt, bool $bold = true): void
    {
        $font = $this->resolveFont($bold);
        if ($font === null || ! function_exists('imagettftext')) {
            $fallback = $bold ? 5 : 3;
            $latin = $this->latin1($text);
            $width = imagefontwidth($fallback) * strlen($latin);
            $height = imagefontheight($fallback);
            imagestringup(
                $image,
                $fallback,
                (int) ($centerX - ($height / 2)),
                (int) ($centerY + ($width / 2)),
                $latin,
                $color,
            );

            return;
        }

        [$x, $y] = $this->ttfBaselineForCenter($sizePt, 90, $font, $text, $centerX, $centerY);
        $this->drawTtf($image, $sizePt, 90, $x, $y, $color, $font, $text);
    }

    /**
     * Calcula el punto baseline de imagettftext para que el texto quede centrado en (centerX, centerY).
     *
     * @return array{0: int, 1: int}
     */
    protected function ttfBaselineForCenter(
        float $sizePt,
        float $angle,
        string $font,
        string $text,
        int $centerX,
        int $centerY,
    ): array {
        $box = imagettfbbox($sizePt, $angle, $font, $text);
        if ($box === false) {
            return [$centerX, $centerY];
        }

        $xs = [$box[0], $box[2], $box[4], $box[6]];
        $ys = [$box[1], $box[3], $box[5], $box[7]];

        return [
            (int) round($centerX - ((min($xs) + max($xs)) / 2)),
            (int) round($centerY - ((min($ys) + max($ys)) / 2)),
        ];
    }

    /**
     * @param  \GdImage|resource  $image
     */
    protected function drawText($image, string $text, int $x, int $y, int $color, float $sizePt, bool $bold = false): void
    {
        $font = $this->resolveFont($bold);
        if ($font === null || ! function_exists('imagettftext')) {
            imagestring($image, 5, $x, max(0, $y - 12), $this->latin1($text), $color);

            return;
        }

        $this->drawTtf($image, $sizePt, 0, $x, $y, $color, $font, $text);
    }

    /**
     * Dibuja texto Arial con halo blanco fino para contraste sobre colores de pieza.
     *
     * @param  \GdImage|resource  $image
     */
    protected function drawTtf($image, float $sizePt, float $angle, int $x, int $y, int $color, string $font, string $text): void
    {
        $halo = imagecolorallocate($image, 255, 255, 255);
        foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dx, $dy]) {
            imagettftext($image, $sizePt, $angle, $x + $dx, $y + $dy, $halo, $font, $text);
        }

        imagettftext($image, $sizePt, $angle, $x, $y, $color, $font, $text);
    }

    protected function resolveFont(bool $bold): ?string
    {
        $this->resolveFonts();

        if ($bold) {
            return $this->fontBoldPath ?? $this->fontRegularPath;
        }

        return $this->fontRegularPath ?? $this->fontBoldPath;
    }

    protected function resolveFonts(): void
    {
        if ($this->fontResolved) {
            return;
        }

        $this->fontResolved = true;

        $boldCandidates = [
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/Library/Fonts/Arial Bold.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial_Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
        ];

        $regularCandidates = [
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/Library/Fonts/Arial.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
        ];

        foreach ($boldCandidates as $candidate) {
            if (is_readable($candidate)) {
                $this->fontBoldPath = $candidate;
                break;
            }
        }

        foreach ($regularCandidates as $candidate) {
            if (is_readable($candidate)) {
                $this->fontRegularPath = $candidate;
                break;
            }
        }
    }

    protected function drawDashedRectangle($image, int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        $dash = 6;
        for ($x = $x1; $x < $x2; $x += $dash * 2) {
            imageline($image, $x, $y1, min($x + $dash, $x2), $y1, $color);
            imageline($image, $x, $y2, min($x + $dash, $x2), $y2, $color);
        }
        for ($y = $y1; $y < $y2; $y += $dash * 2) {
            imageline($image, $x1, $y, $x1, min($y + $dash, $y2), $color);
            imageline($image, $x2, $y, $x2, min($y + $dash, $y2), $color);
        }
    }

    protected function latin1(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        return is_string($converted) ? $converted : $text;
    }
}
