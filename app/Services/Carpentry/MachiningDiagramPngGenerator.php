<?php

namespace App\Services\Carpentry;

class MachiningDiagramPngGenerator
{
    /**
     * @param  list<array{kind: string, label: string, width_mm: int, height_mm: int}>  $diagrams
     */
    public function generateDataUri(array $diagrams): string
    {
        if ($diagrams === []) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode($this->generateBinary($diagrams));
    }

    /**
     * @param  list<array{kind: string, label: string, width_mm: int, height_mm: int}>  $diagrams
     */
    public function generateBinary(array $diagrams): string
    {
        $vertical = [];
        $horizontal = [];

        foreach ($diagrams as $diagram) {
            if (in_array($diagram['kind'] ?? '', ['lateral', 'division'], true)) {
                $vertical[] = $diagram;
            } else {
                $horizontal[] = $diagram;
            }
        }

        $items = array_merge(
            $this->scaledItems($vertical, 280, 90),
            $this->scaledItems($horizontal, 130, 240),
        );

        $canvasW = 860;
        $rows = [];
        $row = [];
        $rowW = 0;
        $rowH = 0;

        foreach ($items as $item) {
            if ($row !== [] && ($rowW + $item['box_w']) > ($canvasW - 24)) {
                $rows[] = ['items' => $row, 'height' => $rowH, 'width' => $rowW];
                $row = [];
                $rowW = 0;
                $rowH = 0;
            }

            $row[] = $item;
            $rowW += $item['box_w'];
            $rowH = max($rowH, $item['box_h']);
        }

        if ($row !== []) {
            $rows[] = ['items' => $row, 'height' => $rowH, 'width' => $rowW];
        }

        $canvasH = 16;
        foreach ($rows as $packed) {
            $canvasH += $packed['height'] + 18;
        }

        $image = imagecreatetruecolor($canvasW, max(80, $canvasH));
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 25, 25, 25);
        $labelColor = imagecolorallocate($image, 153, 27, 27);
        imagefilledrectangle($image, 0, 0, $canvasW, $canvasH, $white);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $y = 10;
        foreach ($rows as $packed) {
            $x = (int) max(8, ($canvasW - $packed['width']) / 2);

            foreach ($packed['items'] as $item) {
                $boardX = $x + (int) (($item['box_w'] - $item['board_w']) / 2);
                $boardY = $y;
                $this->drawBoard(
                    $image,
                    $boardX,
                    $boardY,
                    $item['board_w'],
                    $item['board_h'],
                    (string) $item['diagram']['kind'],
                    $ink,
                    $labelColor,
                );

                $label = (string) $item['diagram']['label'];
                $size = 12;
                $textW = DrawingFont::textWidth($label, $size, true);
                $labelY = $boardY + $item['board_h'] + 18;
                if (($item['diagram']['kind'] ?? '') === 'maletero') {
                    $labelY += 22;
                }
                DrawingFont::draw(
                    $image,
                    $size,
                    $boardX + (int) (($item['board_w'] - $textW) / 2),
                    $labelY,
                    $label,
                    $labelColor,
                    true,
                );

                $x += $item['box_w'];
            }

            $y += $packed['height'] + 18;
        }

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return is_string($binary) ? $binary : '';
    }

    /**
     * @param  list<array{kind: string, label: string, width_mm: int, height_mm: int}>  $diagrams
     * @return list<array{diagram: array{kind: string, label: string, width_mm: int, height_mm: int}, board_w: int, board_h: int, box_w: int, box_h: int}>
     */
    private function scaledItems(array $diagrams, int $targetHeight, int $maxWidth): array
    {
        if ($diagrams === []) {
            return [];
        }

        $scale = $targetHeight / max(1, max(array_map(
            fn (array $diagram): int => (int) $diagram['height_mm'],
            $diagrams,
        )));

        $widest = max(array_map(
            fn (array $diagram): int => (int) $diagram['width_mm'],
            $diagrams,
        ));

        if (($widest * $scale) > $maxWidth) {
            $scale = $maxWidth / max(1, $widest);
        }

        $items = [];

        foreach ($diagrams as $diagram) {
            $boardW = max(36, (int) round($diagram['width_mm'] * $scale));
            $boardH = max(36, (int) round($diagram['height_mm'] * $scale));
            $extra = ($diagram['kind'] ?? '') === 'maletero' ? 30 : 0;
            $items[] = [
                'diagram' => $diagram,
                'board_w' => $boardW,
                'board_h' => $boardH,
                'box_w' => $boardW + 36,
                'box_h' => $boardH + 40 + $extra,
            ];
        }

        return $items;
    }

    private function drawBoard($image, int $x, int $y, int $w, int $h, string $kind, int $ink, int $accent): void
    {
        imagesetthickness($image, 2);
        imagerectangle($image, $x, $y, $x + $w, $y + $h, $ink);
        imagesetthickness($image, 1);

        $insetX = max(6, (int) round($w * 0.08));
        $insetY = max(6, (int) round($h * 0.06));

        $this->hole($image, $x + $insetX, $y + $insetY, $ink);
        $this->hole($image, $x + $w - $insetX, $y + $insetY, $ink);
        $this->hole($image, $x + $insetX, $y + $h - $insetY, $ink);
        $this->hole($image, $x + $w - $insetX, $y + $h - $insetY, $ink);

        if ($kind === 'lateral' || $kind === 'division') {
            $this->hole($image, $x + (int) ($w / 2), $y + $insetY, $ink);
            $this->hole($image, $x + (int) ($w / 2), $y + $h - $insetY, $ink);
            $pins = $kind === 'division' ? 8 : 6;
            $top = $y + (int) round($h * 0.18);
            $bottom = $y + (int) round($h * 0.82);
            $columns = $kind === 'division' ? [0.38, 0.62] : [0.5];

            for ($i = 0; $i < $pins; $i++) {
                $py = $top + (int) round(($bottom - $top) * $i / max(1, $pins - 1));
                foreach ($columns as $column) {
                    $this->hole($image, $x + (int) round($w * $column), $py, $ink);
                }
            }

            return;
        }

        if ($kind === 'base') {
            $this->hole($image, $x + $insetX, $y + (int) ($h / 2), $ink);
            $this->hole($image, $x + $w - $insetX, $y + (int) ($h / 2), $ink);
            $cx = $x + (int) ($w / 2);
            $cy = $y + (int) ($h / 2);
            imagesetthickness($image, 2);
            imageline($image, $cx - 10, $cy - 14, $cx + 10, $cy - 14, $ink);
            imageline($image, $cx - 10, $cy, $cx + 10, $cy, $ink);
            imageline($image, $cx - 10, $cy + 14, $cx + 10, $cy + 14, $ink);
            imagesetthickness($image, 1);

            return;
        }

        if ($kind === 'maletero') {
            $cx = $x + (int) ($w / 2);
            $this->hole($image, $cx, $y + (int) round($h * 0.3), $ink);
            $this->hole($image, $cx, $y + (int) round($h * 0.5), $ink);
            $this->hole($image, $cx, $y + (int) round($h * 0.7), $ink);

            $markY = $y + $h + 8;
            $left = $x + (int) round($w * 0.33);
            $right = $x + (int) round($w * 0.67);
            imageline($image, $left, $markY, $left, $markY + 12, $accent);
            imageline($image, $right, $markY, $right, $markY + 12, $accent);
            $half = '1/2';
            $halfW = DrawingFont::textWidth($half, 11, true);
            DrawingFont::draw($image, 11, (int) (($left + $right - $halfW) / 2), $markY + 12, $half, $accent, true);

            return;
        }

        $this->hole($image, $x + (int) ($w / 2), $y + $insetY, $ink);
        $this->hole($image, $x + (int) ($w / 2), $y + $h - $insetY, $ink);
    }

    private function hole($image, int $x, int $y, int $color): void
    {
        imagefilledellipse($image, $x, $y, 7, 7, $color);
    }
}
