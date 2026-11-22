<?php

namespace App\Services\Carpentry;

class FurnitureElevationPngGenerator
{
    /**
     * Alzado frontal del mueble, en el estilo de la ficha de taller.
     *
     * @param  array{
     *     height_cm: float,
     *     width_cm: float,
     *     modules: int,
     *     verticals: int,
     *     has_trunk: bool,
     *     hanging_bars: int,
     *     module_widths_mm: list<int>
     * }  $spec
     */
    public function generateDataUri(array $spec): string
    {
        $png = $this->generateBinary($spec);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * @param  array{
     *     height_cm: float,
     *     width_cm: float,
     *     modules: int,
     *     verticals: int,
     *     has_trunk: bool,
     *     hanging_bars: int,
     *     module_widths_mm: list<int>
     * }  $spec
     */
    public function generateBinary(array $spec): string
    {
        $modules = max(1, (int) ($spec['modules'] ?? 1));
        $heightCm = max(1.0, (float) ($spec['height_cm'] ?? 1));
        $widthCm = max(1.0, (float) ($spec['width_cm'] ?? 1));
        $aspect = $heightCm / $widthCm;

        $cabinetW = 760;
        $cabinetH = (int) round($cabinetW * $aspect);
        $maxH = 1040;

        if ($cabinetH > $maxH) {
            $cabinetH = $maxH;
            $cabinetW = max(120, (int) round($cabinetH / $aspect));
        }

        $depthCm = max(1.0, (float) ($spec['depth_cm'] ?? ($widthCm * 0.35)));
        $pxPerCm = $cabinetW / $widthCm;
        $angle = deg2rad(32);
        $dx = (int) round($depthCm * $pxPerCm * cos($angle));
        $dy = (int) round($depthCm * $pxPerCm * sin($angle));

        $pad = 10;
        $labelH = $modules > 1 ? 42 : 0;
        $canvasW = $cabinetW + $dx + (2 * $pad);
        $canvasH = $cabinetH + $dy + $labelH + (2 * $pad);
        $originX = $pad;
        $originY = $pad + $dy;

        $image = imagecreatetruecolor($canvasW, $canvasH);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 28, 28, 28);
        imagefilledrectangle($image, 0, 0, $canvasW, $canvasH, $white);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $inner = $this->drawCabinet(
            $image,
            $originX,
            $originY,
            $cabinetW,
            $cabinetH,
            $ink,
            (bool) ($spec['has_trunk'] ?? true),
            $widthCm,
            $dx,
            $dy,
        );

        $this->drawModules(
            $image,
            $inner,
            $modules,
            (int) ($spec['verticals'] ?? 0),
            $spec['module_widths_mm'] ?? [],
            is_array($spec['shelves'] ?? null) ? $spec['shelves'] : [],
            is_array($spec['drawers'] ?? null) ? $spec['drawers'] : [],
            $ink,
            $originY + $cabinetH + 28,
        );

        $this->paintRightSide($image, $originX, $originY, $cabinetW, $cabinetH, $dx, $dy, $ink);

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return is_string($binary) ? $binary : '';
    }

    /**
     * Marco a escala: el grueso sigue al tablero de 16 mm respecto al ancho real.
     *
     * @return array{x: int, y: int, w: int, h: int, rail: int, px: float, dx: int, dy: int}
     */
    private function drawCabinet(
        $image,
        int $x,
        int $y,
        int $w,
        int $h,
        int $ink,
        bool $hasTrunk,
        float $widthCm,
        int $dx,
        int $dy,
    ): array {
        $face = imagecolorallocate($image, 196, 196, 196);
        $top = imagecolorallocate($image, 214, 214, 214);
        $side = imagecolorallocate($image, 158, 158, 158);
        $paper = imagecolorallocate($image, 255, 255, 255);
        $pxPerCm = $w / max(1.0, $widthCm);
        $inset = max(4, (int) round(1.6 * $pxPerCm));
        $ix = $x + $inset;
        $iy = $y + $inset;
        $iw = max(8, $w - (2 * $inset));
        $ih = max(8, $h - (2 * $inset));

        $this->fillQuad($image, [
            [$x, $y],
            [$x + $w, $y],
            [$x + $w + $dx, $y - $dy],
            [$x + $dx, $y - $dy],
        ], $top, $ink);
        $this->fillQuad($image, [
            [$x + $w, $y],
            [$x + $w, $y + $h],
            [$x + $w + $dx, $y + $h - $dy],
            [$x + $w + $dx, $y - $dy],
        ], $side, $ink);

        imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $face);
        imagefilledrectangle($image, $ix, $iy, $ix + $iw, $iy + $ih, $paper);
        imagesetthickness($image, 2);
        imagerectangle($image, $x, $y, $x + $w, $y + $h, $ink);
        imagerectangle($image, $ix, $iy, $ix + $iw, $iy + $ih, $ink);

        $railY = $iy;
        if ($hasTrunk) {
            $railY = $iy + (int) round($ih * 0.13);
            $shelf = $railY + $inset;
            imagefilledrectangle($image, $ix, $railY, $ix + $iw, $shelf, $face);
            $sx = (int) round($dx * 0.28);
            $sy = (int) round($dy * 0.28);
            $this->fillQuad($image, [
                [$ix, $railY],
                [$ix + $iw, $railY],
                [$ix + $iw + $sx, $railY - $sy],
                [$ix + $sx, $railY - $sy],
            ], $top, $ink);
            imagerectangle($image, $ix, $railY, $ix + $iw, $shelf, $ink);
            $railY = $shelf;
        }

        imagesetthickness($image, 1);

        return [
            'x' => $ix,
            'y' => $iy,
            'w' => $iw,
            'h' => $ih,
            'rail' => $railY,
            'px' => $pxPerCm,
            'dx' => $dx,
            'dy' => $dy,
        ];
    }

    private function paintRightSide($image, int $x, int $y, int $w, int $h, int $dx, int $dy, int $ink): void
    {
        $side = imagecolorallocate($image, 158, 158, 158);
        $this->fillQuad($image, [
            [$x + $w, $y],
            [$x + $w, $y + $h],
            [$x + $w + $dx, $y + $h - $dy],
            [$x + $w + $dx, $y - $dy],
        ], $side, $ink);
    }

    /**
     * @param  list<array{0: int, 1: int}>  $points
     */
    private function fillQuad($image, array $points, int $fill, int $ink): void
    {
        $flat = [];
        foreach ($points as $point) {
            $flat[] = $point[0];
            $flat[] = $point[1];
        }

        imagefilledpolygon($image, $flat, $fill);
        imagepolygon($image, $flat, $ink);
    }

    /**
     * @param  array{x: int, y: int, w: int, h: int, rail: int, px: float}  $inner
     * @param  list<int>  $widthsMm
     * @param  list<array{width_cm?: float}>  $shelves
     * @param  array{count?: int, width_cm?: float, height_cm?: float}  $drawers
     */
    private function drawModules(
        $image,
        array $inner,
        int $modules,
        int $verticals,
        array $widthsMm,
        array $shelves,
        array $drawers,
        int $ink,
        int $labelBaseline,
    ): void {
        $shares = [];
        $sum = 0;
        for ($index = 0; $index < $modules; $index++) {
            $share = max(1, (int) ($widthsMm[$index] ?? 0));
            $shares[] = $share;
            $sum += $share;
        }
        if ($sum <= 0) {
            $shares = array_fill(0, $modules, 1);
            $sum = $modules;
        }

        $cursor = $inner['x'];
        $bottom = $inner['y'] + $inner['h'];
        $bays = [];

        for ($index = 0; $index < $modules; $index++) {
            $span = $index === $modules - 1
                ? ($inner['x'] + $inner['w'] - $cursor)
                : (int) round($inner['w'] * $shares[$index] / $sum);
            $bays[] = [
                'x' => $cursor,
                'w' => $span,
                'cm' => $span / max(0.01, $inner['px']),
            ];
            $cursor += $span;
        }

        $drawerTop = $this->drawFittings($image, $inner, $bays, $shelves, $drawers, $ink);

        imagesetthickness($image, 2);
        foreach ($bays as $index => $bay) {
            $lineBottom = $drawerTop < $bottom && $this->drawersSpanAll($drawers, $inner) ? $drawerTop : $bottom;
            $board = max(3, (int) round(1.6 * $inner['px']));
            $sx = (int) round($inner['dx'] * 0.22);
            $sy = (int) round($inner['dy'] * 0.22);
            $post = imagecolorallocate($image, 186, 186, 186);

            if ($index > 0) {
                $px1 = $bay['x'] - (int) ($board / 2);
                imagefilledrectangle($image, $px1, $inner['rail'], $px1 + $board, $lineBottom, $post);
                $this->fillQuad($image, [
                    [$px1 + $board, $inner['rail']],
                    [$px1 + $board + $sx, $inner['rail'] - $sy],
                    [$px1 + $board + $sx, $lineBottom - $sy],
                    [$px1 + $board, $lineBottom],
                ], $post, $ink);
                imagerectangle($image, $px1, $inner['rail'], $px1 + $board, $lineBottom, $ink);
            }

            $lines = max(0, $verticals);
            for ($line = 1; $line <= $lines; $line++) {
                $vx = $bay['x'] + (int) round($bay['w'] * $line / ($lines + 1));
                imageline($image, $vx, $inner['rail'], $vx, $lineBottom, $ink);
            }

            if ($modules > 1) {
                $label = (string) ($widthsMm[$index] ?? '');
                $size = 16;
                $textW = DrawingFont::textWidth($label, $size, true);
                DrawingFont::draw(
                    $image,
                    $size,
                    $bay['x'] + (int) (($bay['w'] - $textW) / 2),
                    $labelBaseline,
                    $label,
                    $ink,
                    true,
                );
            }
        }

        imagesetthickness($image, 1);
    }

    /**
     * @param  array{x: int, y: int, w: int, h: int, rail: int, px: float}  $inner
     * @param  list<array{x: int, w: int, cm: float}>  $bays
     * @param  list<array{width_cm?: float}>  $shelves
     * @param  array{count?: int, width_cm?: float, height_cm?: float}  $drawers
     */
    private function drawFittings($image, array $inner, array $bays, array $shelves, array $drawers, int $ink): int
    {
        $bottom = $inner['y'] + $inner['h'];
        $px = max(0.01, $inner['px']);
        $innerCm = $inner['w'] / $px;
        $count = max(0, (int) ($drawers['count'] ?? 0));
        $drawerWidth = (float) ($drawers['width_cm'] ?? 0);
        $drawerHeight = (float) ($drawers['height_cm'] ?? 0);
        if ($count > 0 && $drawerHeight <= 0) {
            $drawerHeight = 16;
        }

        $fullDrawers = $count > 0 && $this->spanIsFull($drawerWidth, $innerCm);
        $drawerBay = $fullDrawers ? null : ($count > 0 ? $this->bayIndex($drawerWidth, $bays) : null);

        $shelfGroups = array_fill(0, count($bays), 0);
        $fullShelves = 0;
        foreach ($shelves as $shelf) {
            $width = (float) ($shelf['width_cm'] ?? 0);
            if ($this->spanIsFull($width, $innerCm)) {
                $fullShelves++;
                continue;
            }
            $shelfGroups[$this->bayIndex($width, $bays)]++;
        }

        $drawerTop = $bottom;
        imagesetthickness($image, 2);

        if ($count > 0) {
            $zone = $fullDrawers
                ? ['x' => $inner['x'], 'w' => $inner['w']]
                : $bays[$drawerBay];
            $drawerTop = $this->drawDrawerStack($image, $zone, $bottom, $inner['rail'], $count, $drawerWidth, $drawerHeight, $px, $ink);
        }

        if ($fullShelves > 0) {
            $this->drawShelfLines(
                $image,
                $inner['x'],
                $inner['w'],
                $inner['rail'],
                $fullDrawers ? $drawerTop : $bottom,
                $fullShelves,
                $ink,
            );
        }

        foreach ($bays as $index => $bay) {
            if ($shelfGroups[$index] < 1) {
                continue;
            }
            $zoneBottom = (! $fullDrawers && $drawerBay === $index) ? $drawerTop : $bottom;
            $this->drawShelfLines($image, $bay['x'], $bay['w'], $inner['rail'], $zoneBottom, $shelfGroups[$index], $ink);
        }

        imagesetthickness($image, 1);

        return $fullDrawers ? $drawerTop : $bottom;
    }

    /**
     * @param  array{x: int, w: int}  $zone
     */
    private function drawDrawerStack(
        $image,
        array $zone,
        int $bottom,
        int $rail,
        int $count,
        float $widthCm,
        float $heightCm,
        float $px,
        int $ink,
    ): int {
        $gap = max(2, (int) round(0.4 * $px));
        $frontH = max(6, (int) round($heightCm * $px));
        $frontW = $widthCm > 0 ? (int) round($widthCm * $px) : $zone['w'];
        $frontW = max(8, min($zone['w'] - 2, $frontW));
        $available = $bottom - $rail - 8;
        $needed = ($count * $frontH) + (max(0, $count - 1) * $gap);

        if ($needed > $available && $count > 0) {
            $frontH = max(5, (int) floor(($available - (max(0, $count - 1) * $gap)) / $count));
        }

        $x = $zone['x'] + (int) (($zone['w'] - $frontW) / 2);
        $top = $bottom;

        for ($index = 0; $index < $count; $index++) {
            $y2 = $bottom - ($index * ($frontH + $gap));
            $y1 = $y2 - $frontH;
            if ($y1 <= $rail + 2) {
                break;
            }
            imagerectangle($image, $x, $y1, $x + $frontW, $y2, $ink);
            $mid = (int) (($y1 + $y2) / 2);
            $handle = max(6, (int) round($frontW * 0.12));
            imageline($image, $x + (int) ($frontW / 2) - $handle, $mid, $x + (int) ($frontW / 2) + $handle, $mid, $ink);
            $top = $y1;
        }

        return $top;
    }

    private function drawShelfLines($image, int $x, int $w, int $top, int $bottom, int $count, int $ink): void
    {
        $span = $bottom - $top;
        if ($count < 1 || $span < 12) {
            return;
        }

        $board = max(3, (int) round($w * 0.012));
        $gray = imagecolorallocate($image, 170, 170, 170);
        for ($index = 1; $index <= $count; $index++) {
            $y = $top + (int) round($span * $index / ($count + 1));
            imagefilledrectangle($image, $x + 2, $y, $x + $w - 2, $y + $board, $gray);
            imagerectangle($image, $x + 2, $y, $x + $w - 2, $y + $board, $ink);
        }
    }

    /**
     * @param  array{count?: int, width_cm?: float}  $drawers
     * @param  array{w: int, px: float}  $inner
     */
    private function drawersSpanAll(array $drawers, array $inner): bool
    {
        $count = (int) ($drawers['count'] ?? 0);
        if ($count < 1) {
            return false;
        }

        return $this->spanIsFull((float) ($drawers['width_cm'] ?? 0), $inner['w'] / max(0.01, $inner['px']));
    }

    private function spanIsFull(float $widthCm, float $innerCm): bool
    {
        return $widthCm <= 0 || $widthCm >= $innerCm * 0.82;
    }

    /**
     * @param  list<array{x: int, w: int, cm: float}>  $bays
     */
    private function bayIndex(float $widthCm, array $bays): int
    {
        $best = 0;
        $bestDiff = PHP_FLOAT_MAX;

        foreach ($bays as $index => $bay) {
            $diff = abs($bay['cm'] - $widthCm);
            if ($diff < $bestDiff) {
                $best = $index;
                $bestDiff = $diff;
            }
        }

        return $best;
    }
}
