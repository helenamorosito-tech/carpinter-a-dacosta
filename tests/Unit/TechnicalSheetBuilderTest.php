<?php

namespace Tests\Unit;

use App\Services\Carpentry\CutDiagramSvgGenerator;
use App\Services\Carpentry\CutOptimizer;
use App\Services\Carpentry\FurnitureCalculator;
use App\Services\Carpentry\FurnitureElevationPngGenerator;
use App\Services\Carpentry\MachiningDiagramPngGenerator;
use App\Services\Carpentry\PresetCutCalculator;
use App\Services\Carpentry\TechnicalSheetBuilder;
use App\Support\MachiningOptions;
use PHPUnit\Framework\TestCase;

class TechnicalSheetBuilderTest extends TestCase
{
    public function test_builds_workshop_sheet_with_elevation_and_machining(): void
    {
        $calculator = new PresetCutCalculator(
            new FurnitureCalculator,
            new CutOptimizer,
            new CutDiagramSvgGenerator,
        );

        $formData = [
            'name' => 'Armario Palma',
            'opening_height_m' => 2.398,
            'opening_width_m' => 1.773,
            'opening_depth_cm' => 60,
            'global_height_cm' => 243,
            'global_width_cm' => 182.6,
            'global_depth_cm' => 60,
            'furniture_type' => 'armario_2_puertas_correderas',
            'side_type' => 'costado_visto',
            'structure_board_thickness_mm' => 16,
            'back_board_thickness_mm' => 10,
            'back_board_quantity' => 1,
            'selected_boards' => 'textil_16',
            'configuration' => [
                'module_count' => 1,
                'shelf_count' => 4,
                'trunk_shelf_length_cm' => 176.1,
                'trunk_shelf_depth_cm' => 51,
                'trunk_shelf_board_mm' => 16,
                'additional_shelves' => [
                    ['width_cm' => 88.9, 'depth_cm' => 48.5, 'board_mm' => 16],
                    ['width_cm' => 88.9, 'depth_cm' => 48.5, 'board_mm' => 16],
                    ['width_cm' => 88.9, 'depth_cm' => 48.5, 'board_mm' => 16],
                ],
                'interior_laterals' => [
                    ['height_cm' => 195, 'board_mm' => 16],
                ],
                'hanging_bars_count' => 2,
                'hanging_bar_length_cm' => 88.5,
                'drawers_count' => 3,
                'drawer_width_cm' => 81,
                'drawer_height_cm' => 15.5,
                'drawer_depth_cm' => 45,
                'machining' => MachiningOptions::defaults(),
                'machining_suplemento_height_cm' => 6,
                'machining_extra' => [
                    ['name' => 'Bisagra', 'position' => '80 mm desde el canto superior'],
                    ['name' => '  ', 'position' => ''],
                ],
            ],
        ];

        $calculation = $calculator->calculate($formData);
        $sheet = (new TechnicalSheetBuilder(
            new FurnitureElevationPngGenerator,
            new MachiningDiagramPngGenerator,
        ))->build($formData, $calculation);

        $this->assertSame('ARMARIO CORREDERA *2 HOJAS*', $sheet['title']);
        $this->assertSame(2430, $sheet['module']['alto_mm']);
        $this->assertSame(1826, $sheet['module']['ancho_mm']);
        $this->assertSame(600, $sheet['module']['fondo_mm']);
        $this->assertSame(2398, $sheet['hueco']['largo_mm']);
        $this->assertSame(1773, $sheet['hueco']['ancho_mm']);
        $this->assertSame('MELAMINA TEXTIL', $sheet['material']);

        $names = array_column($sheet['parts'], 'name');
        $this->assertSame(
            ['LATERAL', 'BASE', 'TECHO', 'BALDA MALETERO', 'BALDA', 'DIVISION', 'TRASERA', 'BARRA COLGAR'],
            $names,
        );

        $lateral = $sheet['parts'][0];
        $this->assertSame('2 de', $lateral['qty']);
        $this->assertSame('2430 x 600 x 16', $lateral['measures']);

        $this->assertNotEmpty($sheet['hardware']);
        $extra = $sheet['hardware'][array_key_last($sheet['hardware'])];
        $this->assertSame('BISAGRA', $extra['name']);
        $this->assertSame('80 mm desde el canto superior', $extra['measures']);
        $this->assertSame(
            [['name' => 'Bisagra', 'position' => '80 mm desde el canto superior']],
            $sheet['machiningExtras'],
        );
        $this->assertCount(3, $sheet['supplements']);
        $this->assertSame(548, (int) explode(' X ', $sheet['supplements'][1]['measures'])[0]);
        $this->assertTrue($sheet['drawers']['frentes']);
        $this->assertSame(3, $sheet['drawers']['title_count']);
        $this->assertSame(810, $sheet['drawers']['width_mm']);

        $this->assertStringStartsWith('data:image/png;base64,', $sheet['elevation']);
        $this->assertStringStartsWith('data:image/png;base64,', $sheet['machiningImage']);
        $this->assertTrue($sheet['showMachiningPage']);

        $png = base64_decode(substr($sheet['elevation'], strlen('data:image/png;base64,')), true);
        $this->assertIsString($png);
        $this->assertStringStartsWith("\x89PNG", $png);
    }
}
