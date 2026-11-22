<?php

namespace App\Services\Carpentry;

use App\Models\FurnitureProject;
use App\Models\Material;

class ProjectCalculationService
{
    public function __construct(
        private FurnitureCalculator $furnitureCalculator,
        private CutOptimizer $cutOptimizer,
        private CutDiagramSvgGenerator $svgGenerator,
    ) {}

    public function calculateAndPersist(FurnitureProject $project, ?Material $material = null): FurnitureProject
    {
        $material ??= $project->material;

        $preset = $project->preset;
        $doorCount = $preset
            ? ElevationFront::doorCount($preset->furniture_type, (string) $preset->name)
            : 0;

        $furnitureResult = $this->furnitureCalculator->calculate([
            'height_cm' => $project->height_cm,
            'width_cm' => $project->width_cm,
            'depth_cm' => $project->depth_cm,
            'placement_type' => $project->placement_type->value,
            'shelf_count' => $project->shelf_count,
            'shelf_thickness_cm' => $project->shelf_thickness_cm,
            'module_count' => $project->module_count,
            'door_count' => $doorCount,
        ]);

        $cutPlan = ['boards' => [], 'total_boards' => 0, 'used_area_cm2' => 0, 'waste_area_cm2' => 0, 'efficiency_percent' => 0];

        if ($material) {
            $cutPlan = $this->cutOptimizer->optimize(
                (float) $material->width_cm,
                (float) $material->height_cm,
                $furnitureResult['pieces'],
            );
        }

        $svg = $this->svgGenerator->generate($cutPlan);

        $project->update([
            'effective_width_cm' => $furnitureResult['effective_width_cm'],
            'door_count' => $furnitureResult['door_count'],
            'pieces' => $furnitureResult['pieces'],
            'cut_plan' => $cutPlan,
            'svg_diagram' => $svg,
        ]);

        return $project->fresh();
    }
}
