<?php

namespace Tests\Unit;

use App\Services\Carpentry\CutOptimizer;
use App\Services\Carpentry\FurnitureCalculator;
use PHPUnit\Framework\TestCase;

class CarpentryCalculatorTest extends TestCase
{
    public function test_entre_paredes_discounts_width_and_calculates_doors(): void
    {
        $calculator = new FurnitureCalculator;

        $result = $calculator->calculate([
            'height_cm' => 200,
            'width_cm' => 100,
            'depth_cm' => 50,
            'placement_type' => 'entre_paredes',
            'shelf_count' => 2,
            'shelf_thickness_cm' => 1.6,
            'module_count' => 1,
        ]);

        $this->assertSame(91.0, $result['effective_width_cm']);
        $this->assertSame(2, $result['door_count']);
        $this->assertNotEmpty($result['pieces']);
    }

    public function test_cut_optimizer_places_pieces_on_board(): void
    {
        $optimizer = new CutOptimizer;

        $result = $optimizer->optimize(122, 244, [
            ['label' => 'Cuadro', 'width_cm' => 50, 'height_cm' => 50, 'quantity' => 2],
        ]);

        $this->assertSame(1, $result['total_boards']);
        $this->assertCount(2, $result['boards'][0]['placements']);
        $this->assertNotEmpty($result['boards'][0]['remnants']);
    }
}
