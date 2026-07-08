<?php

namespace App\Services\Carpentry;

use App\Models\FurnitureProject;
use Barryvdh\DomPDF\Facade\Pdf;

class CutSheetPdfGenerator
{
    public function __construct(
        private CutDiagramSvgGenerator $svgGenerator,
    ) {}

    public function generate(FurnitureProject $project)
    {
        $cutPlan = $project->cut_plan ?? [];
        $pieces = $project->pieces ?? [];
        $svg = $project->svg_diagram ?: $this->svgGenerator->generate($cutPlan);

        return Pdf::loadView('pdf.cut-sheet', [
            'project' => $project,
            'pieces' => $pieces,
            'cutPlan' => $cutPlan,
            'svg' => $svg,
        ])->setPaper('a4', 'portrait');
    }
}
