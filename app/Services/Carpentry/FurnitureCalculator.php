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
     *     shelf_count: int,
     *     shelf_thickness_cm: float|int,
     *     module_count: int,
     * }  $input
     * @return array{
     *     effective_width_cm: float,
     *     door_count: int,
     *     pieces: list<array{label: string, width_cm: float, height_cm: float, quantity: int}>
     * }
     */
    public function calculate(array $input): array
    {
        $height = (float) $input['height_cm'];
        $width = (float) $input['width_cm'];
        $depth = (float) $input['depth_cm'];
        $placement = $input['placement_type'];
        $shelfCount = (int) $input['shelf_count'];
        $shelfThickness = (float) $input['shelf_thickness_cm'];
        $moduleCount = max(1, (int) $input['module_count']);

        $effectiveWidth = $placement === 'entre_paredes'
            ? max(0, $width - (self::WALL_CLEARANCE_PER_SIDE_CM * 2))
            : $width;

        $doorCount = $this->resolveDoorCount($effectiveWidth);
        $pieces = [];

        foreach (range(1, $moduleCount) as $module) {
            $suffix = $moduleCount > 1 ? " M{$module}" : '';

            $pieces[] = [
                'label' => 'Tapa'.$suffix,
                'width_cm' => round($effectiveWidth, 2),
                'height_cm' => round($depth, 2),
                'quantity' => 1,
            ];

            $pieces[] = [
                'label' => 'Base'.$suffix,
                'width_cm' => round($effectiveWidth, 2),
                'height_cm' => round($depth, 2),
                'quantity' => 1,
            ];

            $pieces[] = [
                'label' => 'Lateral'.$suffix,
                'width_cm' => round($depth, 2),
                'height_cm' => round($height, 2),
                'quantity' => 2,
            ];

            $pieces[] = [
                'label' => 'Fondo'.$suffix,
                'width_cm' => round($effectiveWidth, 2),
                'height_cm' => round($height, 2),
                'quantity' => 1,
            ];

            if ($shelfCount > 0) {
                $shelfWidth = max(0, $effectiveWidth - (2 * $shelfThickness));

                $pieces[] = [
                    'label' => 'Balda'.$suffix,
                    'width_cm' => round($shelfWidth, 2),
                    'height_cm' => round($depth, 2),
                    'quantity' => $shelfCount,
                ];
            }

            $doorWidth = $doorCount > 0 ? $effectiveWidth / $doorCount : 0;

            if ($doorWidth > 0) {
                $pieces[] = [
                    'label' => 'Puerta'.$suffix,
                    'width_cm' => round($doorWidth, 2),
                    'height_cm' => round($height, 2),
                    'quantity' => $doorCount,
                ];
            }
        }

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
