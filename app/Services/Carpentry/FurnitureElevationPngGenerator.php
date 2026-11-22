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
        $moduleWidthCm = $widthCm / $modules;
        $aspect = $heightCm / max(1.0, $moduleWidthCm);

        $cabinetW = 280;
        $cabinetH = (int) round($cabinetW * $aspect);
        $maxH = 820;

        if ($cabinetH > $maxH) {
            $cabinetH = $maxH;
            $cabinetW = max(90, (int) round($cabinetH / $aspect));
        }

        $pad = 6;
        $gap = $modules > 1 ? 26 : 0;
        $labelH = $modules > 1 ? 40 : 0;
        $canvasW = ($cabinetW * $modules) + ($gap * max(0, $modules - 1)) + (2 * $pad);
        $canvasH = $cabinetH + $labelH + (2 * $pad);
        $originX = $pad;
        $originY = $pad;

        $image = imagecreatetruecolor($canvasW, $canvasH);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 28, 28, 28);
        imagefilledrectangle($image, 0, 0, $canvasW, $canvasH, $white);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $widths = $spec['module_widths_mm'] ?? [];

        for ($index = 0; $index < $modules; $index++) {
            $x = $originX + ($index * ($cabinetW + $gap));

            $this->drawCabinet(
                $image,
                $x,
                $originY,
                $cabinetW,
                $cabinetH,
                $ink,
                (int) ($spec['verticals'] ?? 0),
                (bool) ($spec['has_trunk'] ?? true),
            );

            if ($modules > 1) {
                $label = (string) ($widths[$index] ?? '');
                $size = 18;
                $textW = DrawingFont::textWidth($label, $size, true);
                DrawingFont::draw(
                    $image,
                    $size,
                    $x + (int) (($cabinetW - $textW) / 2),
                    $originY + $cabinetH + 26,
                    $label,
                    $ink,
                    true,
                );
            }
        }

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return is_string($binary) ? $binary : '';
    }

    private function drawCabinet(
        $image,
        int $x,
        int $y,
        int $w,
        int $h,
        int $ink,
        int $verticals,
        bool $hasTrunk,
    ): void {
        $frame = imagecolorallocate($image, 214, 214, 214);
        $paper = imagecolorallocate($image, 255, 255, 255);
        $inset = max(18, min(34, (int) round(min($w, $h) * 0.08)));
        $ix = $x + $inset;
        $iy = $y + $inset;
        $iw = max(8, $w - (2 * $inset));
        $ih = max(8, $h - (2 * $inset));

        imagefilledrectangle($image, $x, $y, $x + $w, $y + $h, $frame);
        imagefilledrectangle($image, $ix, $iy, $ix + $iw, $iy + $ih, $paper);
        imagesetthickness($image, 3);
        imagerectangle($image, $x, $y, $x + $w, $y + $h, $ink);
        imagesetthickness($image, 2);
        imagerectangle($image, $ix, $iy, $ix + $iw, $iy + $ih, $ink);

        $railY = $iy;
        if ($hasTrunk) {
            $railY = $iy + (int) round($ih * 0.13);
            imageline($image, $ix, $railY, $ix + $iw, $railY, $ink);
        }

        $verticals = max(0, $verticals);
        for ($i = 1; $i <= $verticals; $i++) {
            $vx = $ix + (int) round($iw * $i / ($verticals + 1));
            imageline($image, $vx, $railY, $vx, $iy + $ih, $ink);
        }

        imagesetthickness($image, 1);
    }
}
