<?php

namespace App\Services\Carpentry;

class FurnitureCalculator
{
    private const WALL_CLEARANCE_PER_SIDE_CM = 4.5;

    /**
     * @param  array{
     *     height_cm: float|int,
     *     width_cm: float|int,
     *     depth_cm: float|int,
     *     placement_type: string,
     *     shelf_count?: int,
     *     shelf_thickness_cm?: float|int,
     *     module_count?: int,
     *     door_count?: int,
     *     include_interior_pieces?: bool,
     *     back_board_quantity?: int,
     * }  $input
     * @return array{
     *     effective_width_cm: float,
     *     door_count: int,
     *     pieces: list<array{label: string, width_cm: float, height_cm: float, quantity: int, board_role?: string}>
     * }
     */
    public function calculate(array $input): array
    {
        $height = (float) $input['height_cm'];
        $width = (float) $input['width_cm'];
        $depth = (float) $input['depth_cm'];
        $placement = $input['placement_type'];
        $shelfCount = (int) ($input['shelf_count'] ?? 0);
        $shelfThickness = (float) ($input['shelf_thickness_cm'] ?? 1.6);
        $moduleCount = max(1, (int) ($input['module_count'] ?? 1));
        $includeInterior = (bool) ($input['include_interior_pieces'] ?? true);
        $backBoardQuantity = max(1, (int) ($input['back_board_quantity'] ?? 1));

        $effectiveWidth = $placement === 'entre_paredes'
            ? max(0, $width - (self::WALL_CLEARANCE_PER_SIDE_CM * 2))
            : $width;

        $doorCount = max(0, (int) ($input['door_count'] ?? 0));
        if ($doorCount < 1) {
            $doorCount = $this->resolveDoorCount($effectiveWidth);
        }
        $pieces = [];

        foreach (range(1, $moduleCount) as $module) {
            $suffix = $moduleCount > 1 ? " M{$module}" : '';

            // Techo: corte = ancho × profundidad globales, tablero 16 mm (estructura).
            $pieces[] = [
                'label' => 'Techo del mueble'.$suffix,
                'width_cm' => round($width, 2),
                'height_cm' => round($depth, 2),
                'quantity' => 1,
                'board_role' => 'structure',
            ];

            $pieces[] = [
                'label' => 'Base del mueble'.$suffix,
                'width_cm' => round($effectiveWidth, 2),
                'height_cm' => round($depth, 2),
                'quantity' => 1,
                'board_role' => 'structure',
            ];

            if (! $includeInterior) {
                continue;
            }

            if ($shelfCount > 0) {
                $shelfWidth = max(0, $effectiveWidth - (2 * $shelfThickness));

                $pieces[] = [
                    'label' => 'Balda'.$suffix,
                    'width_cm' => round($shelfWidth, 2),
                    'height_cm' => round($depth, 2),
                    'quantity' => $shelfCount,
                    'board_role' => 'structure',
                ];
            }

            $doorWidth = $doorCount > 0 ? $effectiveWidth / $doorCount : 0;

            if ($doorWidth > 0) {
                $pieces[] = [
                    'label' => 'Puerta'.$suffix,
                    'width_cm' => round($doorWidth, 2),
                    'height_cm' => round($height, 2),
                    'quantity' => $doorCount,
                    'board_role' => 'structure',
                ];
            }
        }

        // 2 laterales por mueble: izquierdo y derecho (profundidad × alto).
        $pieces[] = [
            'label' => 'Lateral izquierdo',
            'width_cm' => round($depth, 2),
            'height_cm' => round($height, 2),
            'quantity' => 1,
            'board_role' => 'structure',
        ];

        $pieces[] = [
            'label' => 'Lateral derecho',
            'width_cm' => round($depth, 2),
            'height_cm' => round($height, 2),
            'quantity' => 1,
            'board_role' => 'structure',
        ];

        // Fondos: por defecto 1 (editable), medida = ancho × alto exteriores, grosor 10 mm (editable).
        $pieces[] = [
            'label' => 'Fondo del mueble',
            'width_cm' => round($width, 2),
            'height_cm' => round($height, 2),
            'quantity' => $backBoardQuantity,
            'board_role' => 'back',
        ];

        return [
            'effective_width_cm' => round($effectiveWidth, 2),
            'door_count' => $doorCount,
            'pieces' => $pieces,
        ];
    }

    public function resolveDoorCount(float $widthCm): int
    {
        if ($widthCm <= 100) {
            return 2;
        }

        if ($widthCm <= 180) {
            return 3;
        }

        return 4;
    }
}
