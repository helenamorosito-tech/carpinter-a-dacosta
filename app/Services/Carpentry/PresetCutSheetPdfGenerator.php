<?php

namespace App\Services\Carpentry;

use App\Enums\PlacementType;
use App\Models\FurniturePreset;
use App\Support\BoardMaterialOptions;
use App\Support\MeasureFormat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PresetCutSheetPdfGenerator
{
    public const PREVIEW_TTL_MINUTES = 20;

    public const PREVIEW_DISK_DIRECTORY = 'cut-sheet-previews';

    public function __construct(
        private PresetCutCalculator $calculator,
        private CutDiagramSvgGenerator $svgGenerator,
        private CutDiagramPngGenerator $pngGenerator,
        private FurnitureElevationPngGenerator $elevation,
        private MachiningDiagramPngGenerator $machining,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     */
    public function storePreviewFromFormData(array $formData, ?string $filename = null): string
    {
        $pdf = $this->generateFromFormData($formData);

        return $this->storePreviewBinary(
            $pdf->output(),
            $filename ?? 'plan-corte-mueble-'.now()->format('Ymd-His').'.pdf',
        );
    }

    public function storePreview(FurniturePreset $preset): string
    {
        $pdf = $this->generate($preset);

        return $this->storePreviewBinary(
            $pdf->output(),
            'plan-corte-'.($preset->slug ?: 'mueble').'.pdf',
        );
    }

    public function storePreviewBinary(string $content, string $filename): string
    {
        $this->purgeExpiredPreviews();

        $token = (string) Str::uuid();
        $safeFilename = Str::of($filename)->basename()->toString() ?: 'plan-corte.pdf';

        Storage::disk('local')->put($this->pdfPath($token), $content);
        Storage::disk('local')->put($this->metaPath($token), json_encode([
            'filename' => $safeFilename,
            'expires_at' => now()->addMinutes(self::PREVIEW_TTL_MINUTES)->timestamp,
        ], JSON_THROW_ON_ERROR));

        return $token;
    }

    /**
     * @return array{content: string, filename: string}|null
     */
    public function retrievePreview(string $token): ?array
    {
        $disk = Storage::disk('local');
        $pdfPath = $this->pdfPath($token);
        $metaPath = $this->metaPath($token);

        if (! $disk->exists($pdfPath) || ! $disk->exists($metaPath)) {
            return null;
        }

        $meta = json_decode($disk->get($metaPath), true);

        if (! is_array($meta) || ($meta['expires_at'] ?? 0) < now()->timestamp) {
            $disk->delete([$pdfPath, $metaPath]);

            return null;
        }

        return [
            'content' => $disk->get($pdfPath),
            'filename' => $meta['filename'] ?? 'plan-corte.pdf',
        ];
    }

    public function previewUrl(string $token): string
    {
        return route('cut-sheet.preview', ['token' => $token]);
    }

    public function downloadUrl(string $token): string
    {
        return route('cut-sheet.download', ['token' => $token]);
    }

    protected function pdfPath(string $token): string
    {
        return self::PREVIEW_DISK_DIRECTORY.'/'.$token.'.pdf';
    }

    protected function metaPath(string $token): string
    {
        return self::PREVIEW_DISK_DIRECTORY.'/'.$token.'.json';
    }

    protected function purgeExpiredPreviews(): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::PREVIEW_DISK_DIRECTORY)) {
            return;
        }

        foreach ($disk->files(self::PREVIEW_DISK_DIRECTORY) as $file) {
            if (! str_ends_with($file, '.json')) {
                continue;
            }

            $meta = json_decode($disk->get($file), true);
            $expiresAt = is_array($meta) ? (int) ($meta['expires_at'] ?? 0) : 0;

            if ($expiresAt >= now()->timestamp) {
                continue;
            }

            $token = Str::before(basename($file), '.json');
            $disk->delete([$file, $this->pdfPath($token)]);
        }
    }

    /**
     * @param  array<string, mixed>  $formData
     */
    public function generateFromFormData(array $formData)
    {
        $calculation = $this->calculator->calculate($formData);
        $payload = $this->buildPayload($formData, $calculation);

        return $this->finalizePdf(
            Pdf::loadView('pdf.preset-cut-sheet', $payload)->setPaper('a4', 'portrait'),
            (string) ($payload['furnitureType'] ?? '—'),
        );
    }

    public function generate(FurniturePreset $preset)
    {
        $formData = [
            'name' => $preset->name,
            'opening_height_m' => $preset->opening_height_m,
            'opening_width_m' => $preset->opening_width_m,
            'opening_depth_cm' => $preset->opening_depth_cm,
            'global_height_cm' => $preset->global_height_cm,
            'global_width_cm' => $preset->global_width_cm,
            'global_depth_cm' => $preset->global_depth_cm,
            'furniture_type' => $preset->furniture_type,
            'side_type' => $preset->side_type?->value,
            'configuration' => $preset->configuration ?? [],
            'board_width_cm' => $preset->board_width_cm,
            'board_height_cm' => $preset->board_height_cm,
            'selected_boards' => $preset->selected_boards ?? [],
            'structure_board_thickness_mm' => $preset->structure_board_thickness_mm ?? 16,
            'back_board_thickness_mm' => $preset->back_board_thickness_mm,
            'back_board_quantity' => $preset->back_board_quantity,
        ];

        $calculation = [
            'pieces' => $preset->pieces ?? [],
            'cut_plan' => $preset->cut_plan ?? [],
            'svg_diagram' => $preset->svg_diagram,
            'calculated_boards_count' => $preset->calculated_boards_count ?? 0,
            'back_board_quantity' => $preset->back_board_quantity ?? 0,
        ];

        if (empty($calculation['cut_plan']['boards'])) {
            $calculation = $this->calculator->calculate($formData);
        } else {
            $calculation['cut_plan'] = $this->nameRemnants($calculation['cut_plan']);
            $calculation['svg_diagram'] = $this->svgGenerator->generate($calculation['cut_plan']);
            $backStock = collect($calculation['cut_plan']['boards'] ?? [])
                ->where('board_kind', 'fondo')
                ->count();
            $calculation['back_stock_boards'] = $backStock;
            $calculation['total_boards_needed'] = (int) ($calculation['calculated_boards_count'] ?? 0) + $backStock;
            $calculation['back_board_thickness_mm'] = $preset->back_board_thickness_mm ?? FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM;
            $calculation['structure_board_thickness_mm'] = $preset->structure_board_thickness_mm ?? 16;
        }

        $payload = $this->buildPayload($formData, $calculation);

        return $this->finalizePdf(
            Pdf::loadView('pdf.preset-cut-sheet', $payload)->setPaper('a4', 'portrait'),
            (string) ($payload['furnitureType'] ?? '—'),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $structureDiagrams
     * @param  list<array<string, mixed>>  $backDiagrams
     * @return list<list<array<string, mixed>>>
     */
    private function cutDiagramPages(array $structureDiagrams, array $backDiagrams, int $structureThickness, int $backThickness): array
    {
        $diagrams = [];

        foreach ($structureDiagrams as $diagram) {
            $diagram['thickness_mm'] = $structureThickness;
            $diagrams[] = $diagram;
        }

        foreach ($backDiagrams as $diagram) {
            $diagram['thickness_mm'] = $backThickness;
            $diagrams[] = $diagram;
        }

        return array_chunk($diagrams, 2);
    }

    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $configuration
     * @param  list<array<string, mixed>>  $pieces
     */
    private function elevationDataUri(array $formData, array $configuration, array $pieces): string
    {
        $heightCm = (float) ($formData['global_height_cm'] ?? 0);
        $widthCm = (float) ($formData['global_width_cm'] ?? 0);

        if ($heightCm <= 0 || $widthCm <= 0) {
            return '';
        }

        $modules = max(1, (int) ($configuration['module_count'] ?? 1));
        $front = ElevationFront::layout(
            $formData['furniture_type'] ?? null,
            (string) ($formData['name'] ?? ''),
            $modules,
            $widthCm,
        );

        $hasTrunk = (float) ($configuration['trunk_shelf_length_cm'] ?? 0) > 0;
        if (! $hasTrunk) {
            foreach ($pieces as $piece) {
                if (str_starts_with((string) ($piece['label'] ?? ''), 'Balda maletero')) {
                    $hasTrunk = true;
                    break;
                }
            }
        }

        return $this->elevation->generateDataUri([
            'height_cm' => $heightCm,
            'width_cm' => $widthCm,
            'modules' => $modules,
            'verticals' => 0,
            'bay_verticals' => $front['verticals'],
            'module_widths_mm' => $front['widths_mm'],
            'has_trunk' => $hasTrunk,
            'hanging_bars' => max(0, (int) ($configuration['hanging_bars_count'] ?? 0)),
            'depth_cm' => (float) ($formData['global_depth_cm'] ?? 0),
            'shelves' => $this->elevationShelves($configuration),
            'drawers' => $this->elevationDrawers($configuration),
        ]);
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<array{width_cm: float}>
     */
    private function elevationShelves(array $configuration): array
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
            $shelves[] = ['width_cm' => 0];
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

    /**
     * Numeración de páginas y encabezado con tipo de mueble desde la 2.ª página.
     *
     * @param  \Barryvdh\DomPDF\PDF  $pdf
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function finalizePdf($pdf, string $furnitureType)
    {
        $pdf->render();

        $canvas = $pdf->getDomPDF()->getCanvas();

        $canvas->page_script(function (int $pageNumber, int $pageCount, $canvas, $fontMetrics): void {
            $font = $fontMetrics->getFont('DejaVu Sans');
            $pageWidth = $canvas->get_width();
            $pageHeight = $canvas->get_height();

            // Oculta el encabezado fijo HTML en la primera página (ya aparece en los datos).
            if ($pageNumber === 1) {
                $canvas->filled_rectangle(0.0, 0.0, $pageWidth, 40.0, [1.0, 1.0, 1.0]);
            }

            $pageLabel = "Página {$pageNumber} de {$pageCount}";
            $labelSize = 8.0;
            $labelWidth = $fontMetrics->getTextWidth($pageLabel, $font, $labelSize);
            $canvas->text(
                ($pageWidth - $labelWidth) / 2,
                $pageHeight - 22.0,
                $pageLabel,
                $font,
                $labelSize,
                [0.42, 0.45, 0.50],
            );
        });

        return $pdf;
    }

    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $calculation
     * @return array<string, mixed>
     */
    protected function buildPayload(array $formData, array $calculation): array
    {
        $cutPlan = $this->nameRemnants($calculation['cut_plan'] ?? []);
        $pieces = $calculation['pieces'] ?? [];
        $configuration = $formData['configuration'] ?? [];
        $configuration['additional_shelves'] = FurniturePreset::normalizeAdditionalShelves(
            is_array($configuration) ? $configuration : [],
            (float) ($formData['global_depth_cm'] ?? 0),
            (float) ($formData['global_width_cm'] ?? 0),
        );
        $configuration['interior_laterals'] = FurniturePreset::normalizeInteriorLaterals(
            is_array($configuration) ? $configuration : [],
            (float) ($formData['global_height_cm'] ?? 0),
        );
        $configuration['interior_laterals_count'] = count($configuration['interior_laterals']);
        $configuration = FurniturePreset::normalizeHangingBarLengthCm($configuration);

        $furnitureTypeLabel = ElevationFront::typeLabel(
            $formData['furniture_type'] ?? null,
            (string) ($formData['name'] ?? ''),
        );

        $sideType = PlacementType::tryFrom((string) ($formData['side_type'] ?? ''));

        $selectedBoards = BoardMaterialOptions::parseMany(
            is_array($formData['selected_boards'] ?? null) ? $formData['selected_boards'] : []
        );

        $backQty = max(1, (int) ($calculation['back_board_quantity'] ?? $formData['back_board_quantity'] ?? 1));
        $backStock = (int) ($calculation['back_stock_boards'] ?? 0);
        if ($backStock <= 0) {
            $backStock = collect($cutPlan['boards'] ?? [])
                ->where('board_kind', 'fondo')
                ->count();
        }
        $backThickness = (int) ($calculation['back_board_thickness_mm'] ?? $formData['back_board_thickness_mm'] ?? FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM);
        $structureThickness = (int) ($calculation['structure_board_thickness_mm'] ?? $formData['structure_board_thickness_mm'] ?? 16);

        $boardsToUse = [];
        $structureDiagrams = [];
        $backDiagrams = [];
        $fondoSheetIndex = 0;
        $boardTitlesByIndex = [];

        foreach ($cutPlan['boards'] ?? [] as $board) {
            $isBack = ($board['board_kind'] ?? null) === 'fondo';
            $placements = [];
            foreach ($board['placements'] ?? [] as $placement) {
                $placements[] = [
                    ...$placement,
                    'use' => $this->pieceUsage((string) ($placement['label'] ?? '')),
                ];
            }
            $board['placements'] = $placements;
            $boardUse = $this->boardUsageLabel($placements, $isBack);
            $board['use'] = $boardUse;

            if ($isBack) {
                $fondoSheetIndex++;
                $global = (int) ($board['index'] ?? $fondoSheetIndex);
                $boardName = "Tablero {$global} ({$backThickness} mm)";
            } else {
                $global = (int) ($board['index'] ?? $board['local_index'] ?? 0);
                $boardName = "Tablero {$global} ({$structureThickness} mm)";
            }

            $board['title'] = $boardName;
            $boardTitlesByIndex[(int) ($board['index'] ?? 0)] = $boardName;

            $boardsToUse[] = [
                'name' => $boardName,
                'type' => $isBack
                    ? "Tablero {$backThickness} mm (fondo)"
                    : "Tablero {$structureThickness} mm",
                'use' => $boardUse,
                'width_cm' => $board['width_cm'],
                'height_cm' => $board['height_cm'],
                'pieces_count' => count($board['placements'] ?? []),
                'remnants_count' => count($board['remnants'] ?? []),
            ];

            $diagram = [
                'index' => $board['index'],
                'width_cm' => $board['width_cm'],
                'height_cm' => $board['height_cm'],
                'image' => $this->pngGenerator->generateBoardDataUriForPdf($board),
                'board' => $board,
            ];

            if ($isBack) {
                $backDiagrams[] = $diagram;
            } else {
                $structureDiagrams[] = $diagram;
            }
        }

        $namedPieces = [];
        $pieceIndex = 1;
        foreach ($pieces as $piece) {
            $qty = max(1, (int) ($piece['quantity'] ?? 1));
            $label = (string) ($piece['label'] ?? 'Pieza');
            $thicknessMm = $this->pieceThicknessMm($piece, $label, $structureThickness, $backThickness);

            for ($i = 1; $i <= $qty; $i++) {
                $suffix = $qty > 1 ? " #{$i}" : '';
                $namedPieces[] = [
                    'name' => $label.$suffix,
                    'use' => $this->pieceUsage($label),
                    'code' => 'P'.str_pad((string) $pieceIndex, 2, '0', STR_PAD_LEFT),
                    'width_cm' => $piece['width_cm'],
                    'height_cm' => $piece['height_cm'],
                    'thickness_mm' => $thicknessMm,
                    'area_cm2' => round(((float) $piece['width_cm']) * ((float) $piece['height_cm']), 2),
                ];
                $pieceIndex++;
            }
        }

        $interiorLaterals = $this->buildInteriorLateralsSummary($configuration, $formData);

        $namedRemnants = [];
        foreach ($cutPlan['boards'] ?? [] as $board) {
            $boardIndex = (int) ($board['index'] ?? 0);
            $boardTitle = $boardTitlesByIndex[$boardIndex]
                ?? ($board['title'] ?? ('Tablero '.$boardIndex));

            foreach ($board['remnants'] ?? [] as $remnant) {
                $namedRemnants[] = [
                    'name' => $remnant['label'] ?? 'Sobrante',
                    'board' => $boardTitle,
                    'width_cm' => $remnant['width'],
                    'height_cm' => $remnant['height'],
                    'x' => $remnant['x'],
                    'y' => $remnant['y'],
                    'area_cm2' => round(((float) $remnant['width']) * ((float) $remnant['height']), 2),
                ];
            }
        }

        $title = $formData['name']
            ?? ($furnitureTypeLabel ?? 'Mueble').' — plan de corte';

        $sideDetail = $sideType?->getLabel() ?? '—';
        if ($sideType === PlacementType::CostadoVisto) {
            $sides = [];
            if (! empty($configuration['costado_visto_izquierda'])) {
                $sides[] = 'izquierda';
            }
            if (! empty($configuration['costado_visto_derecha'])) {
                $sides[] = 'derecha';
            }
            if ($sides !== []) {
                $sideDetail .= ' ('.implode(' y ', $sides).')';
            }
        }

        $svg = $calculation['svg_diagram']
            ?? $this->svgGenerator->generate($cutPlan);

        return [
            'title' => $title,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'furnitureType' => $furnitureTypeLabel ?? '—',
            'opening' => [
                'height_m' => $formData['opening_height_m'] ?? null,
                'width_m' => $formData['opening_width_m'] ?? null,
                'depth_cm' => $formData['opening_depth_cm'] ?? null,
            ],
            'global' => [
                'height_cm' => $formData['global_height_cm'] ?? null,
                'width_cm' => $formData['global_width_cm'] ?? null,
                'depth_cm' => $formData['global_depth_cm'] ?? null,
            ],
            'sideDetail' => $sideDetail,
            'boardSize' => [
                'width_cm' => $formData['board_width_cm'] ?? FurniturePreset::DEFAULT_BOARD_WIDTH_CM,
                'height_cm' => $formData['board_height_cm'] ?? FurniturePreset::DEFAULT_BOARD_HEIGHT_CM,
            ],
            'showBoardBase' => $this->shouldShowBoardBase($formData, $cutPlan),
            'materials' => array_map(fn (array $item): string => $item['label'], $selectedBoards),
            'materialUsageRows' => $this->buildMaterialUsageRowsWithAntRemnants(
                $boardsToUse,
                $configuration,
                $structureThickness,
                $backThickness,
                $backQty,
                (float) ($formData['global_width_cm'] ?? 0),
                (float) ($formData['global_height_cm'] ?? 0),
                $pieces,
            ),
            'antRemnantMatches' => $this->matchAntRemnants($pieces, $structureThickness),
            'structureBoard' => [
                'thickness_mm' => $structureThickness,
                'quantity' => (int) ($calculation['calculated_boards_count'] ?? 0),
            ],
            'backBoard' => [
                'thickness_mm' => $backThickness,
                'quantity' => $backQty,
                'stock_boards' => $backStock,
            ],
            'configuration' => $configuration,
            'interiorLaterals' => $interiorLaterals,
            'boardsToUse' => $boardsToUse,
            'namedPieces' => $namedPieces,
            'pieceSummary' => $this->summarizePieces($namedPieces),
            'namedRemnants' => $namedRemnants,
            'drawers' => $this->buildDrawers($configuration, $formData),
            'hangingBars' => $this->buildHangingBars($configuration),
            'cutPlan' => $cutPlan,
            'svg' => $svg,
            'elevation' => $this->elevationDataUri($formData, $configuration, $pieces),
            'machiningImage' => $this->machining->generateDataUri($this->machiningDiagrams($pieces)),
            'structureBoardPages' => array_chunk($structureDiagrams, 1),
            'backBoardPages' => array_chunk($backDiagrams, 1),
            'cutDiagramPages' => $this->cutDiagramPages($structureDiagrams, $backDiagrams, $structureThickness, $backThickness),
            'totals' => [
                'main_boards' => (int) ($calculation['calculated_boards_count'] ?? 0),
                'back_boards' => $backQty,
                'back_stock_boards' => $backStock > 0 ? $backStock : $backQty,
                'fondo_count' => $backQty,
                'total_boards' => (int) ($calculation['total_boards_needed']
                    ?? ((int) ($calculation['calculated_boards_count'] ?? 0) + $backStock)),
                'efficiency_percent' => $cutPlan['efficiency_percent'] ?? 0,
                'used_area_cm2' => $cutPlan['used_area_cm2'] ?? 0,
                'waste_area_cm2' => $cutPlan['waste_area_cm2'] ?? 0,
            ],
            'filename' => 'plan-corte-'.Str::slug($title).'.pdf',
        ];
    }

    /**
     * Materiales del mueble: primero sobrantes Ant aprovechables, luego tableros nuevos.
     *
     * @param  list<array<string, mixed>>  $boardsToUse
     * @param  array<string, mixed>  $configuration
     * @param  list<array<string, mixed>>  $pieces
     * @return list<array{name: string, use: string, measures: string, thickness: string, from_ant?: bool}>
     */
    protected function buildMaterialUsageRowsWithAntRemnants(
        array $boardsToUse,
        array $configuration,
        int $structureThickness,
        int $backThickness,
        int $backQty,
        float $globalWidthCm,
        float $globalHeightCm,
        array $pieces,
    ): array {
        $antRows = [];
        foreach ($this->matchAntRemnants($pieces, $structureThickness) as $match) {
            $antRows[] = [
                'name' => $match['name'],
                'use' => $match['use'],
                'measures' => $match['measures'],
                'thickness' => $match['thickness'],
                'from_ant' => true,
                'origin' => $match['origin'] ?? null,
            ];
        }

        return array_merge(
            $antRows,
            $this->buildMaterialUsageRows(
                $boardsToUse,
                $configuration,
                $structureThickness,
                $backThickness,
                $backQty,
                $globalWidthCm,
                $globalHeightCm,
            ),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $pieces
     * @return list<array<string, mixed>>
     */
    protected function matchAntRemnants(array $pieces, int $structureThickness): array
    {
        return app(AntFurnitureRemnantMatcher::class)->matchForPieces($pieces, $structureThickness);
    }

    /**
     * Filas de la sección «Materiales seleccionados»: tableros y piezas con su uso.
     *
     * @param  list<array<string, mixed>>  $boardsToUse
     * @param  array<string, mixed>  $configuration
     * @return list<array{name: string, use: string, measures: string, thickness: string}>
     */
    protected function buildMaterialUsageRows(
        array $boardsToUse,
        array $configuration,
        int $structureThickness,
        int $backThickness,
        int $backQty = 1,
        float $globalWidthCm = 0,
        float $globalHeightCm = 0,
    ): array {
        $rows = [];

        foreach ($boardsToUse as $board) {
            $name = (string) ($board['name'] ?? 'Tablero');
            if (str_contains(mb_strtolower($name), 'fondo')) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'use' => (string) ($board['use'] ?? '—'),
                'measures' => MeasureFormat::pair(
                    (float) ($board['width_cm'] ?? 0),
                    (float) ($board['height_cm'] ?? 0),
                ),
                'thickness' => $structureThickness.' mm',
            ];
        }

        $fondoQty = max(FurniturePreset::DEFAULT_BACK_BOARD_QUANTITY, $backQty);
        $fondoThickness = $backThickness > 0
            ? $backThickness
            : FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM;
        $fondoWidth = $globalWidthCm > 0 ? $globalWidthCm : 0;
        $fondoHeight = $globalHeightCm > 0 ? $globalHeightCm : 0;

        $rows[] = [
            'name' => $fondoQty === 1
                ? 'Fondo del mueble'
                : "Fondo del mueble × {$fondoQty}",
            'use' => 'Fondo',
            'measures' => $fondoWidth > 0 && $fondoHeight > 0
                ? MeasureFormat::pair($fondoWidth, $fondoHeight)
                : '—',
            'thickness' => $fondoThickness.' mm',
        ];

        $trunkLength = (float) ($configuration['trunk_shelf_length_cm'] ?? 0);
        $trunkDepth = (float) ($configuration['trunk_shelf_depth_cm'] ?? 0);
        $trunkBoardMm = (int) ($configuration['trunk_shelf_board_mm'] ?? 16);

        if ($trunkLength > 0 && $trunkDepth > 0) {
            $rows[] = [
                'name' => 'Balda maletero',
                'use' => 'Balda maletero',
                'measures' => MeasureFormat::pair($trunkLength, $trunkDepth),
                'thickness' => $trunkBoardMm.' mm',
            ];
        }

        $additionalShelves = $configuration['additional_shelves'] ?? [];
        if (is_array($additionalShelves)) {
            foreach ($additionalShelves as $index => $shelf) {
                if (! is_array($shelf)) {
                    continue;
                }

                $width = (float) ($shelf['width_cm'] ?? 0);
                $depth = (float) ($shelf['depth_cm'] ?? 0);
                if ($width <= 0 || $depth <= 0) {
                    continue;
                }

                $n = $index + 1;
                $boardMm = (int) ($shelf['board_mm'] ?? 16);
                $rows[] = [
                    'name' => "Balda adicional {$n}",
                    'use' => "Balda adicional {$n}",
                    'measures' => MeasureFormat::pair($width, $depth),
                    'thickness' => $boardMm.' mm',
                ];
            }
        }

        return $rows;
    }

    /**
     * Solo se indica «Tablero base» si hay medida distinta del estándar 122 × 244 cm.
     *
     * @param  array<string, mixed>  $formData
     * @param  array<string, mixed>  $cutPlan
     */
    protected function shouldShowBoardBase(array $formData, array $cutPlan): bool
    {
        $defaultWidth = FurniturePreset::DEFAULT_BOARD_WIDTH_CM;
        $defaultHeight = FurniturePreset::DEFAULT_BOARD_HEIGHT_CM;

        $configuredWidth = (float) ($formData['board_width_cm'] ?? $defaultWidth);
        $configuredHeight = (float) ($formData['board_height_cm'] ?? $defaultHeight);

        if (
            abs($configuredWidth - $defaultWidth) > 0.009
            || abs($configuredHeight - $defaultHeight) > 0.009
        ) {
            return true;
        }

        foreach ($cutPlan['boards'] ?? [] as $board) {
            $width = (float) ($board['width_cm'] ?? $configuredWidth);
            $height = (float) ($board['height_cm'] ?? $configuredHeight);

            if (abs($width - $defaultWidth) > 0.009 || abs($height - $defaultHeight) > 0.009) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $pieces
     * @return list<array{kind: string, label: string, width_mm: int, height_mm: int}>
     */
    private function machiningDiagrams(array $pieces): array
    {
        $vertical = [];
        $horizontal = [];

        foreach ($pieces as $piece) {
            $spec = $this->machiningSpec((string) ($piece['label'] ?? ''));
            if ($spec === null) {
                continue;
            }

            $widthMm = (int) round(((float) ($piece['width_cm'] ?? 0)) * 10);
            $heightMm = (int) round(((float) ($piece['height_cm'] ?? 0)) * 10);
            if ($widthMm <= 0 || $heightMm <= 0) {
                continue;
            }

            $entry = [
                'kind' => $spec['kind'],
                'label' => $spec['label'],
                'width_mm' => $widthMm,
                'height_mm' => $heightMm,
                'quantity' => max(1, (int) ($piece['quantity'] ?? 1)),
                'sort' => $spec['sort'],
            ];

            if ($spec['vertical']) {
                for ($i = 0; $i < $entry['quantity']; $i++) {
                    $vertical[] = $entry;
                }

                continue;
            }

            $horizontal[] = $entry;
        }

        $grouped = [];
        foreach ($horizontal as $item) {
            $key = $item['kind'].'|'.$item['width_mm'].'|'.$item['height_mm'];
            if (! isset($grouped[$key])) {
                $grouped[$key] = $item;
                $grouped[$key]['quantity'] = 0;
            }
            $grouped[$key]['quantity'] += $item['quantity'];
        }

        $horizontal = array_values($grouped);
        usort($horizontal, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        $diagrams = [];

        foreach ($vertical as $item) {
            $diagrams[] = [
                'kind' => $item['kind'],
                'label' => $item['label'],
                'width_mm' => $item['width_mm'],
                'height_mm' => $item['height_mm'],
            ];
        }

        foreach ($horizontal as $item) {
            $label = $item['label'];
            if ($item['kind'] === 'maletero' || $item['quantity'] > 1) {
                $label = $item['quantity'].' '.$label;
            }

            $diagrams[] = [
                'kind' => $item['kind'],
                'label' => $label,
                'width_mm' => $item['width_mm'],
                'height_mm' => $item['height_mm'],
            ];
        }

        return $diagrams;
    }

    /**
     * @return array{kind: string, label: string, vertical: bool, sort: int}|null
     */
    private function machiningSpec(string $label): ?array
    {
        $name = mb_strtolower($label);

        return match (true) {
            str_contains($name, 'fondo') || str_contains($name, 'puerta') => null,
            str_contains($name, 'lateral interior') => ['kind' => 'division', 'label' => 'DIVISION', 'vertical' => true, 'sort' => 15],
            str_contains($name, 'lateral izquierdo') => ['kind' => 'lateral', 'label' => 'LATERAL IZQ', 'vertical' => true, 'sort' => 10],
            str_contains($name, 'lateral derecho') => ['kind' => 'lateral', 'label' => 'LATERAL DCHO', 'vertical' => true, 'sort' => 11],
            str_contains($name, 'lateral') => ['kind' => 'lateral', 'label' => 'LATERAL', 'vertical' => true, 'sort' => 12],
            str_contains($name, 'base') => ['kind' => 'base', 'label' => 'BASE', 'vertical' => false, 'sort' => 20],
            str_contains($name, 'maletero') => ['kind' => 'maletero', 'label' => 'BALDA MALETERO', 'vertical' => false, 'sort' => 30],
            str_contains($name, 'balda') => ['kind' => 'balda', 'label' => 'BALDA', 'vertical' => false, 'sort' => 40],
            str_contains($name, 'techo') => ['kind' => 'techo', 'label' => 'TECHO', 'vertical' => false, 'sort' => 50],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $piece
     */
    private function pieceThicknessMm(array $piece, string $label, int $structureThickness, int $backThickness): int
    {
        if (preg_match('/\((\d+)\s*mm\)/u', $label, $matches) === 1) {
            return (int) $matches[1];
        }

        $role = (string) ($piece['board_role'] ?? '');
        if ($role === 'back' || str_contains(mb_strtolower($label), 'fondo')) {
            return $backThickness;
        }

        return $structureThickness;
    }

    /**
     * @param  list<array<string, mixed>>  $namedPieces
     * @return list<array{title: string, quantity: int, width_cm: mixed, height_cm: mixed, thickness_mm: int}>
     */
    private function summarizePieces(array $namedPieces): array
    {
        $groups = [];

        foreach ($namedPieces as $piece) {
            $rawName = (string) preg_replace('/\s+#\d+$/u', '', (string) ($piece['name'] ?? 'Pieza'));
            $title = $this->pieceUsageShort($rawName);
            $width = $piece['width_cm'] ?? null;
            $height = $piece['height_cm'] ?? null;
            $thickness = (int) ($piece['thickness_mm'] ?? 0);
            $key = $title.'|'.$width.'|'.$height.'|'.$thickness;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'title' => $title,
                    'quantity' => 0,
                    'width_cm' => $width,
                    'height_cm' => $height,
                    'thickness_mm' => $thickness,
                ];
            }

            $groups[$key]['quantity']++;
        }

        return array_values($groups);
    }

    protected function pieceUsage(string $label): string
    {
        $normalized = mb_strtolower($label);

        if (preg_match('/lateral interior\s*(\d+)/u', $normalized, $matches)) {
            return $this->interiorLateralUseLabel((int) $matches[1]);
        }

        return match (true) {
            str_contains($normalized, 'techo') => 'Uso: Techo del mueble (ancho × profundidad, 16 mm)',
            str_contains($normalized, 'base') => 'Uso: Base / suelo del exterior',
            str_contains($normalized, 'lateral interior') => 'Lateral izq.',
            str_contains($normalized, 'lateral izquierdo') => 'Uso: Lateral izquierdo del exterior',
            str_contains($normalized, 'lateral derecho') => 'Uso: Lateral derecho del exterior',
            str_contains($normalized, 'lateral') => 'Uso: Lateral / lado del exterior',
            str_contains($normalized, 'fondo') => 'Uso: Fondo / trasero del mueble (tablero normal, 1 corte)',
            str_contains($normalized, 'maletero') => 'Uso: Balda maletero (16 mm; ancho global − 6,5 cm × profundidad − 9 cm)',
            str_contains($normalized, 'balda adicional') => 'Uso: Balda adicional',
            str_contains($normalized, 'balda') => 'Uso: Balda interior',
            str_contains($normalized, 'puerta') => 'Uso: Puerta',
            default => 'Uso: Pieza del mueble',
        };
    }

    /**
     * Etiqueta corta de uso para el listado de tableros (sección 3 del PDF).
     *
     * @param  list<array<string, mixed>>  $placements
     */
    protected function boardUsageLabel(array $placements, bool $isBack): string
    {
        if ($placements === []) {
            return $isBack ? 'Fondo' : '—';
        }

        $uses = [];
        foreach ($placements as $placement) {
            $short = $this->pieceUsageShort((string) ($placement['label'] ?? ''));
            if ($short !== '' && ! in_array($short, $uses, true)) {
                $uses[] = $short;
            }
        }

        if ($uses === []) {
            return $isBack ? 'Fondo' : '—';
        }

        return implode(', ', $uses);
    }

    protected function pieceUsageShort(string $label): string
    {
        $normalized = mb_strtolower($label);

        if (preg_match('/lateral interior\s*(\d+)/u', $normalized, $matches)) {
            return $this->interiorLateralUseLabel((int) $matches[1]);
        }

        if (preg_match('/balda adicional\s*(\d+)/u', $normalized, $matches)) {
            return 'Balda adicional '.$matches[1];
        }

        return match (true) {
            str_contains($normalized, 'techo') => 'Techo',
            str_contains($normalized, 'base') => 'Base',
            str_contains($normalized, 'lateral izquierdo') => 'Lateral izquierdo',
            str_contains($normalized, 'lateral derecho') => 'Lateral derecho',
            str_contains($normalized, 'fondo') => 'Fondo',
            str_contains($normalized, 'maletero') => 'Balda maletero',
            str_contains($normalized, 'balda adicional') => 'Balda adicional',
            str_contains($normalized, 'balda') => 'Balda',
            str_contains($normalized, 'puerta') => 'Puerta',
            default => trim((string) preg_replace('/\s*\(\d+\s*mm\)\s*/u', '', $label)) ?: 'Pieza',
        };
    }

    protected function interiorLateralUseLabel(int $index): string
    {
        return match ($index) {
            1 => 'Lateral interior izq.',
            2 => 'Lateral interior der.',
            default => 'Lateral interior '.$index,
        };
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $formData
     * @return list<array{code: string, name: string, width_cm: float|null, height_cm: float|null, depth_cm: float|null}>
     */
    protected function buildDrawers(array $configuration, array $formData): array
    {
        $count = max(0, (int) ($configuration['drawers_count'] ?? 0));
        if ($count < 1) {
            return [];
        }

        $widthCm = (float) ($configuration['drawer_width_cm'] ?? 0);
        $heightCm = (float) ($configuration['drawer_height_cm'] ?? 0);
        $depthCm = (float) (
            $configuration['drawer_depth_cm']
            ?? $configuration['shelf_depth_cm']
            ?? $formData['global_depth_cm']
            ?? 0
        );

        $drawers = [];

        for ($i = 1; $i <= $count; $i++) {
            $drawers[] = [
                'code' => sprintf('C%02d', $i),
                'name' => "Cajón {$i}",
                'width_cm' => $widthCm > 0 ? round($widthCm, 2) : null,
                'height_cm' => $heightCm > 0 ? round($heightCm, 2) : null,
                'depth_cm' => $depthCm > 0 ? round($depthCm, 2) : null,
            ];
        }

        return $drawers;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return list<array{code: string, name: string, length_cm: float|null}>
     */
    protected function buildHangingBars(array $configuration): array
    {
        $configuration = FurniturePreset::normalizeHangingBarLengthCm($configuration);
        $count = max(0, (int) ($configuration['hanging_bars_count'] ?? 0));
        if ($count < 1) {
            return [];
        }

        $lengthCm = (float) ($configuration['hanging_bar_length_cm'] ?? 0);
        $hasLength = $lengthCm > 0;
        $bars = [];

        for ($i = 1; $i <= $count; $i++) {
            $bars[] = [
                'code' => sprintf('B%02d', $i),
                'name' => "Barra de colgar {$i}",
                'length_cm' => $hasLength ? round($lengthCm, 2) : null,
            ];
        }

        return $bars;
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<string, mixed>  $formData
     * @return list<array{use: string, board_mm: int, width_cm: float, height_cm: float}>
     */
    protected function buildInteriorLateralsSummary(array $configuration, array $formData): array
    {
        $heightCm = (float) ($formData['global_height_cm'] ?? 0);
        $depthCm = (float) ($formData['global_depth_cm'] ?? 0);
        $laterals = FurniturePreset::normalizeInteriorLaterals($configuration, $heightCm);
        $rows = [];

        foreach ($laterals as $index => $lateral) {
            $rows[] = [
                'use' => $this->interiorLateralUseLabel($index + 1),
                'board_mm' => (int) ($lateral['board_mm'] ?? 16),
                'width_cm' => round($depthCm, 2),
                'height_cm' => round((float) ($lateral['height_cm'] ?? 0), 2),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $cutPlan
     * @return array<string, mixed>
     */
    protected function nameRemnants(array $cutPlan): array
    {
        $boards = $cutPlan['boards'] ?? [];

        foreach ($boards as $boardIndex => $board) {
            $remnants = $board['remnants'] ?? [];

            foreach ($remnants as $remnantIndex => $remnant) {
                if (! empty($remnant['label'])) {
                    continue;
                }

                $boards[$boardIndex]['remnants'][$remnantIndex]['label'] = sprintf(
                    'S-%02d',
                    $remnantIndex + 1,
                );
            }
        }

        $cutPlan['boards'] = $boards;

        return $cutPlan;
    }
}
