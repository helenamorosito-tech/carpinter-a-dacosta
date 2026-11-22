<?php

namespace App\Services\Carpentry;

use App\Support\BoardMaterialOptions;
use App\Support\FurnitureTypeCatalog;
use App\Support\MachiningOptions;

class TechnicalSheetBuilder
{
    public function __construct(
        private FurnitureElevationPngGenerator $elevation,
        private MachiningDiagramPngGenerator $machiningDiagrams,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $calculation
     * @return array<string, mixed>
     */
    public function build(array $formData, array $calculation): array
    {
        $configuration = is_array($formData['configuration'] ?? null) ? $formData['configuration'] : [];
        $heightCm = (float) ($formData['global_height_cm'] ?? 0);
        $widthCm = (float) ($formData['global_width_cm'] ?? 0);
        $depthCm = (float) ($formData['global_depth_cm'] ?? 0);
        $structureMm = (int) ($calculation['structure_board_thickness_mm'] ?? $formData['structure_board_thickness_mm'] ?? 16);
        $backMm = (int) ($calculation['back_board_thickness_mm'] ?? $formData['back_board_thickness_mm'] ?? 10);

        $selected = $configuration['machining'] ?? null;
        $machining = is_array($selected) ? array_values(array_map('strval', $selected)) : MachiningOptions::defaults();

        $type = FurnitureTypeCatalog::resolve($formData['furniture_type'] ?? null);
        $doorCount = ElevationFront::doorCount($formData['furniture_type'] ?? null, (string) ($formData['name'] ?? ''));
        $isCorrederas = (bool) ($type['is_correderas'] ?? false);
        $title = $this->sheetTitle($type['label'] ?? null, $doorCount, $isCorrederas);

        $modules = max(1, (int) ($configuration['module_count'] ?? 1));
        $parts = $this->partRows($calculation['pieces'] ?? [], $structureMm, $backMm);
        $parts = $this->appendHangingBars($parts, $configuration);
        $hardware = $this->hardwareRows($configuration, $machining, $modules);
        $supplements = MachiningOptions::enabled($machining, MachiningOptions::SUPLEMENTOS)
            ? $this->supplementRows($configuration, $widthCm, $depthCm, $structureMm, $modules)
            : [];
        $drawers = $this->drawerBlock($configuration, $structureMm, $backMm, $machining);

        $front = ElevationFront::layout(
            $formData['furniture_type'] ?? null,
            (string) ($formData['name'] ?? ''),
            $modules,
            $widthCm,
        );

        $elevation = ($heightCm > 0 && $widthCm > 0)
            ? $this->elevation->generateDataUri([
                'height_cm' => $heightCm,
                'width_cm' => $widthCm,
                'modules' => $modules,
                'verticals' => 0,
                'bay_verticals' => $front['verticals'],
                'module_widths_mm' => $front['widths_mm'],
                'has_trunk' => $this->hasTrunk($parts),
                'hanging_bars' => max(0, (int) ($configuration['hanging_bars_count'] ?? 0)),
                'depth_cm' => $depthCm,
                'shelves' => $this->elevationShelves($configuration, $widthCm),
                'drawers' => $this->elevationDrawers($configuration),
            ])
            : '';

        $diagrams = $this->diagramsFor($parts, $machining);
        $machiningImage = $this->machiningDiagrams->generateDataUri(
            $diagrams,
            MachiningOptions::positions($configuration),
        );

        $openingHeightMm = $this->mm(((float) ($formData['opening_height_m'] ?? 0)) * 100);
        $openingWidthMm = $this->mm(((float) ($formData['opening_width_m'] ?? 0)) * 100);

        return [
            'title' => $title,
            'reference' => isset($formData['name']) && $formData['name'] !== '' ? (string) $formData['name'] : $title,
            'generatedAt' => date('d/m/Y'),
            'material' => $this->materialLabel($formData['selected_boards'] ?? null),
            'module' => [
                'alto_mm' => $this->mm($heightCm),
                'ancho_mm' => $this->mm($widthCm),
                'fondo_mm' => $this->mm($depthCm),
            ],
            'hueco' => [
                'largo_mm' => $openingHeightMm > 0 ? $openingHeightMm : $this->mm($heightCm),
                'ancho_mm' => $openingWidthMm > 0 ? $openingWidthMm : $this->mm($widthCm),
                'zocalo_mm' => 0,
                'unidades' => 1,
            ],
            'parts' => $parts,
            'hardware' => $hardware,
            'supplements' => $supplements,
            'drawers' => $drawers,
            'elevation' => $elevation,
            'machiningImage' => $machiningImage,
            'showMachiningPage' => $machiningImage !== '',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $pieces
     * @return list<array{name: string, qty: string, measures: string, width_mm: int, height_mm: int, orientation: string}>
     */
    private function partRows(array $pieces, int $structureMm, int $backMm): array
    {
        $order = [
            'LATERAL' => 10,
            'BASE' => 20,
            'TECHO' => 30,
            'BALDA MALETERO' => 40,
            'BALDA' => 50,
            'DIVISION' => 60,
            'TRASERA' => 70,
        ];

        $groups = [];

        foreach ($pieces as $piece) {
            $label = (string) ($piece['label'] ?? '');
            $name = $this->technicalName($label);

            if ($name === null) {
                continue;
            }

            $widthCm = (float) ($piece['width_cm'] ?? 0);
            $heightCm = (float) ($piece['height_cm'] ?? 0);
            $quantity = max(1, (int) ($piece['quantity'] ?? 1));
            $thickness = $this->thicknessFromLabel($label, $name === 'TRASERA' ? $backMm : $structureMm);
            $orientation = in_array($name, ['LATERAL', 'DIVISION'], true) ? 'vertical' : 'horizontal';
            $first = $orientation === 'vertical' ? $heightCm : $widthCm;
            $second = $orientation === 'vertical' ? $widthCm : $heightCm;
            $key = $name.'|'.$this->mm($first).'|'.$this->mm($second).'|'.$thickness;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $name,
                    'quantity' => 0,
                    'first_mm' => $this->mm($first),
                    'second_mm' => $this->mm($second),
                    'thickness' => $thickness,
                    'orientation' => $orientation,
                    'sort' => $order[$name] ?? 80,
                ];
            }

            $groups[$key]['quantity'] += $quantity;
        }

        $rows = array_values($groups);
        usort($rows, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return array_map(function (array $row): array {
            return [
                'name' => $row['name'],
                'qty' => $row['quantity'].' de',
                'measures' => $row['first_mm'].' x '.$row['second_mm'].' x '.$row['thickness'],
                'width_mm' => $row['orientation'] === 'vertical' ? $row['second_mm'] : $row['first_mm'],
                'height_mm' => $row['orientation'] === 'vertical' ? $row['first_mm'] : $row['second_mm'],
                'orientation' => $row['orientation'],
            ];
        }, $rows);
    }

    /**
     * @param  list<array{name: string, qty: string, measures: string, width_mm: int, height_mm: int, orientation: string}>  $parts
     * @param  array<string, mixed>  $configuration
     * @return list<array{name: string, qty: string, measures: string, width_mm: int, height_mm: int, orientation: string}>
     */
    private function appendHangingBars(array $parts, array $configuration): array
    {
        $count = max(0, (int) ($configuration['hanging_bars_count'] ?? 0));
        $lengthCm = (float) ($configuration['hanging_bar_length_cm'] ?? 0);

        if ($count < 1 || $lengthCm <= 0) {
            return $parts;
        }

        $parts[] = [
            'name' => 'BARRA COLGAR',
            'qty' => $count.' de',
            'measures' => (string) $this->mm($lengthCm),
            'width_mm' => $this->mm($lengthCm),
            'height_mm' => 0,
            'orientation' => 'bar',
        ];

        return $parts;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  list<string>  $machining
     * @return list<array{name: string, qty: string, measures: string}>
     */
    private function hardwareRows(array $configuration, array $machining, int $modules): array
    {
        $rows = [];

        if (MachiningOptions::enabled($machining, MachiningOptions::SOPORTE_LATERAL)) {
            $qty = $configuration['machining_soporte_lateral_qty'] ?? null;
            $count = ($qty === null || $qty === '')
                ? max(4, 2 * ($modules + 1))
                : max(0, (int) $qty);
            $central = MachiningOptions::enabled($machining, MachiningOptions::SOPORTE_CENTRAL)
                ? 'SOPORTE CENTRAL'
                : '';
            $rows[] = [
                'name' => 'SOPORTE LATERAL',
                'qty' => (string) $count,
                'measures' => $central,
            ];
        } elseif (MachiningOptions::enabled($machining, MachiningOptions::SOPORTE_CENTRAL)) {
            $rows[] = [
                'name' => 'SOPORTE CENTRAL',
                'qty' => '',
                'measures' => '',
            ];
        }

        if (MachiningOptions::enabled($machining, MachiningOptions::PORTABALDAS)) {
            $shelves = is_array($configuration['additional_shelves'] ?? null)
                ? count($configuration['additional_shelves'])
                : 0;
            $explicit = $configuration['machining_portabaldas_qty'] ?? null;
            $count = ($explicit === null || $explicit === '')
                ? max(4, 4 * max(1, $shelves))
                : max(0, (int) $explicit);
            $rows[] = [
                'name' => 'PORTABALDAS',
                'qty' => (string) $count,
                'measures' => '',
            ];
        }

        if (MachiningOptions::enabled($machining, MachiningOptions::TORNILLO)) {
            $rows[] = [
                'name' => 'TORNILLO ENSAMBLAJE',
                'qty' => '',
                'measures' => '',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<array{name: string, qty: string, measures: string}>
     */
    private function supplementRows(array $configuration, float $widthCm, float $depthCm, int $thickness, int $modules): array
    {
        $heightCm = (float) ($configuration['machining_suplemento_height_cm'] ?? 6);
        if ($heightCm <= 0) {
            $heightCm = 6;
        }

        $heightMm = $this->mm($heightCm);
        $widthMm = $this->mm($widthCm);
        $lateralMm = max(0, $this->mm($depthCm) - (2 * $thickness) - 20);

        return [
            [
                'name' => 'FRENTE Y TRASERA',
                'qty' => '2 DE',
                'measures' => $widthMm.' X '.$heightMm.' X '.$thickness,
            ],
            [
                'name' => 'LATERALES',
                'qty' => '2 DE',
                'measures' => $lateralMm.' X '.$heightMm.' X '.$thickness,
            ],
            [
                'name' => 'CENTRAL',
                'qty' => $modules.' DE',
                'measures' => $lateralMm.' X '.$heightMm.' X '.$thickness,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  list<string>  $machining
     * @return array{show: bool, frentes: bool, title_count: int, width_mm: int, lines: list<string>}|null
     */
    private function drawerBlock(array $configuration, int $structureMm, int $backMm, array $machining): ?array
    {
        $count = max(0, (int) ($configuration['drawers_count'] ?? 0));

        if ($count < 1) {
            return null;
        }

        $width = (float) ($configuration['drawer_width_cm'] ?? 0);
        $height = (float) ($configuration['drawer_height_cm'] ?? 0);
        $depth = (float) ($configuration['drawer_depth_cm'] ?? 0);
        $lines = [];

        if ($depth > 0 && $height > 0) {
            $lines[] = sprintf('%d LATERAL DE %d X %d X %d', $count * 2, $this->mm($depth), $this->mm($height), $structureMm);
        }

        if ($width > 0 && $height > 0) {
            $lines[] = sprintf('%d FRENTE DE %d X %d X %d', $count, $this->mm($width), $this->mm($height), $structureMm);
        }

        if ($width > 0 && $depth > 0) {
            $lines[] = sprintf('%d FONDO DE %d X %d X %d', $count, $this->mm($width), $this->mm($depth), $backMm);
        }

        return [
            'show' => true,
            'frentes' => MachiningOptions::enabled($machining, MachiningOptions::FRENTES),
            'title_count' => $count,
            'width_mm' => $width > 0 ? $this->mm($width) : 0,
            'lines' => $lines,
        ];
    }

    /**
     * @param  list<array{name: string, qty: string, measures: string, width_mm: int, height_mm: int, orientation: string}>  $parts
     * @param  list<string>  $machining
     * @return list<array{kind: string, label: string, width_mm: int, height_mm: int}>
     */
    private function diagramsFor(array $parts, array $machining): array
    {
        $map = [
            'LATERAL' => [MachiningOptions::LATERALES, 'lateral', 'LATERAL'],
            'DIVISION' => [MachiningOptions::DIVISION, 'division', 'DIVISION'],
            'BASE' => [MachiningOptions::BASE, 'base', 'BASE'],
            'BALDA MALETERO' => [MachiningOptions::MALETERO, 'maletero', 'BALDA MALETERO'],
            'TECHO' => [MachiningOptions::TECHO, 'techo', 'TECHO'],
        ];

        $diagrams = [];

        foreach ($parts as $part) {
            $spec = $map[$part['name']] ?? null;

            if ($spec === null || ! MachiningOptions::enabled($machining, $spec[0])) {
                continue;
            }

            if ($part['width_mm'] <= 0 || $part['height_mm'] <= 0) {
                continue;
            }

            $diagrams[] = [
                'kind' => $spec[1],
                'label' => $spec[2],
                'width_mm' => $part['width_mm'],
                'height_mm' => $part['height_mm'],
            ];
        }

        return $diagrams;
    }

    private function technicalName(string $label): ?string
    {
        return match (true) {
            str_starts_with($label, 'Lateral interior') => 'DIVISION',
            str_starts_with($label, 'Lateral ') => 'LATERAL',
            str_starts_with($label, 'Base') => 'BASE',
            str_starts_with($label, 'Techo') => 'TECHO',
            str_starts_with($label, 'Balda maletero') => 'BALDA MALETERO',
            str_starts_with($label, 'Balda adicional'), str_starts_with($label, 'Balda') => 'BALDA',
            str_starts_with($label, 'Fondo') => 'TRASERA',
            default => null,
        };
    }

    private function thicknessFromLabel(string $label, int $fallback): int
    {
        if (preg_match('/\((\d+)\s*mm\)/', $label, $matches) === 1) {
            return (int) $matches[1];
        }

        return $fallback;
    }

    /**
     * @param  array{label?: string, is_correderas?: bool, door_count?: int}|null  $type
     */
    private function sheetTitle(?string $label, int $doorCount, bool $isCorrederas): string
    {
        if ($isCorrederas && $doorCount > 0) {
            return 'ARMARIO CORREDERA *'.$doorCount.' HOJAS*';
        }

        $title = mb_strtoupper($label ?: 'MUEBLE');

        return $title;
    }

    private function materialLabel(mixed $selected): string
    {
        $keys = [];

        if (is_string($selected) && $selected !== '') {
            $keys = [$selected];
        } elseif (is_array($selected)) {
            $keys = array_values(array_filter(array_map('strval', $selected)));
        }

        $parsed = BoardMaterialOptions::parse((string) ($keys[0] ?? ''));

        if ($parsed === null) {
            return 'MELAMINA';
        }

        return 'MELAMINA '.mb_strtoupper($parsed['color']->getLabel());
    }

    /**
     * @param  list<array{name: string}>  $parts
     */
    /**
     * @param  array<string, mixed>  $configuration
     * @return list<array{width_cm: float}>
     */
    private function elevationShelves(array $configuration, float $widthCm): array
    {
        $shelves = [];
        $listed = $configuration['additional_shelves'] ?? null;

        if (is_array($listed) && $listed !== []) {
            foreach ($listed as $shelf) {
                if (! is_array($shelf)) {
                    continue;
                }
                $shelves[] = ['width_cm' => (float) ($shelf['width_cm'] ?? 0)];
            }

            return $shelves;
        }

        $extra = max(0, (int) ($configuration['shelf_count'] ?? 0) - 1);
        for ($index = 0; $index < $extra; $index++) {
            $shelves[] = ['width_cm' => $widthCm > 0 ? max(0, $widthCm - 13) : 0];
        }

        return $shelves;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array{count: int, width_cm: float, height_cm: float}
     */
    private function elevationDrawers(array $configuration): array
    {
        return [
            'count' => max(0, (int) ($configuration['drawers_count'] ?? 0)),
            'width_cm' => (float) ($configuration['drawer_width_cm'] ?? 0),
            'height_cm' => (float) ($configuration['drawer_height_cm'] ?? 0),
        ];
    }

    private function hasTrunk(array $parts): bool
    {
        foreach ($parts as $part) {
            if ($part['name'] === 'BALDA MALETERO') {
                return true;
            }
        }

        return false;
    }

    private function mm(float $cm): int
    {
        return (int) round($cm * 10);
    }
}
