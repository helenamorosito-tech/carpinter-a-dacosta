<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 48px 28px 36px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.35; }
        .running-header {
            position: fixed;
            top: -32px;
            left: 0;
            right: 0;
            height: 22px;
        }
        .running-header .hl-type {
            margin-bottom: 0;
        }
        h1 { font-size: 15px; margin: 0 0 4px; }
        h2 { font-size: 11px; margin: 18px 0 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        h3 { font-size: 10px; margin: 14px 0 6px; }
        strong, b { font-size: 10px; }
        .meta { color: #4b5563; margin-bottom: 10px; }
        .meta div { margin-bottom: 2px; }
        .meta strong { font-size: 10px; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; width: 50%; padding-right: 8px; }
        table.facts { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.facts td { width: 33.33%; vertical-align: top; padding: 1px 10px 7px 0; }
        .fact-label { font-size: 8px; font-weight: bold; color: #4b5563; line-height: 1.2; }
        .fact-value { font-size: 10px; font-weight: bold; color: #111827; margin-top: 1px; line-height: 1.25; }
        .fact-sub { font-size: 9px; color: #111827; margin-top: 1px; line-height: 1.25; }
        table.measure-box { border-collapse: collapse; margin-top: 2px; }
        table.measure-box th, table.measure-box td {
            border: 1px solid #111827;
            text-align: center;
            padding: 1px 5px;
            line-height: 1.15;
        }
        table.measure-box th {
            font-size: 7px;
            font-weight: bold;
            color: #374151;
            background: #f3f4f6;
            text-transform: uppercase;
        }
        table.measure-box td { font-size: 9px; font-weight: bold; color: #111827; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; }
        table.data th { background: #f3f4f6; font-size: 9px; }
        .summary { margin-top: 8px; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; }
        .summary strong { font-size: 10px; }
        .diagram { margin-top: 4px; text-align: center; }
        .board-page { page-break-before: always; }
        .boards-pair { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .boards-pair > tbody > tr > td { width: 100%; vertical-align: top; padding: 0; }
        .board-block h3 { font-size: 9px; margin: 4px 0 2px; }
        .board-badge {
            display: inline-block;
            background: #f472b6;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            padding: 2px 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .piece-use {
            font-weight: bold;
            font-size: 9px;
            color: #111827;
            line-height: 1.25;
        }
        .board-use {
            font-weight: bold;
            font-size: 10px;
            color: #111827;
            margin: 4px 0 4px;
        }
        .remnant-inline-legend {
            margin: 0 0 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #dc2626;
            line-height: 1.35;
        }
        .remnant-inline-legend .remnant-inline-item {
            display: block;
        }
        .diagram img { max-width: 180mm; max-height: 100mm; width: auto; height: auto; display: block; margin: 0 auto; }
        table.data.compact th, table.data.compact td { padding: 2px 4px; font-size: 8px; }
        table.data.compact td.piece-use { font-size: 8px; }
        table.data.compact.pieces-table,
        table.data.pieces-list {
            table-layout: fixed;
            width: 60%;
        }
        table.data.compact.pieces-table th,
        table.data.pieces-list th {
            background: #bbf7d0;
            color: #14532d;
        }
        table.data.compact.remnants-table {
            margin-top: 6px;
            table-layout: fixed;
            width: 60%;
        }
        table.data.compact.remnants-table th,
        table.data.remnants-list th {
            background: #fecaca;
            color: #7f1d1d;
            font-weight: bold;
        }
        table.data.remnants-list {
            table-layout: fixed;
            margin-top: 2px;
            width: 60%;
        }
        table.data.remnants-list th,
        table.data.remnants-list td {
            padding: 1px 4px;
            font-size: 9px;
            line-height: 1.15;
            vertical-align: middle;
        }
        table.data.remnants-list th {
            font-size: 8px;
            padding: 2px 4px;
        }
        table.data.remnants-list .col-name { width: 16%; }
        table.data.remnants-list .col-board { width: 54%; }
        table.data.remnants-list .col-num { width: 15%; }
        table.data.remnants-list td.col-board {
            font-weight: bold;
            font-size: 10px;
            color: #111827;
        }
        .remnant-code { font-weight: bold; font-size: 9px; }
        h2.section-tight {
            margin: 8px 0 3px;
            padding-bottom: 2px;
            font-size: 10px;
        }
        table.data .measure,
        table.data td.col-size,
        table.data td.col-num.measure {
            font-weight: bold;
            color: #111827;
        }
        table.data.compact td.measure {
            font-weight: bold;
        }
        table.sheet-split { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.sheet-split td { vertical-align: top; }
        td.sheet-main { width: 46%; padding-right: 16px; }
        td.sheet-draw { width: 54%; text-align: center; padding-left: 10px; }
        td.sheet-main table.boards-list { width: 100%; }
        .sheet-draw .caption { display: block; font-size: 8px; font-weight: bold; margin: 2px 0 8px; }
        .sheet-draw img { width: 78mm; height: auto; }
        table.boards-list { margin-top: 4px; table-layout: fixed; }
        table.boards-list th, table.boards-list td {
            padding: 2px 3px;
            font-size: 8px;
            line-height: 1.2;
            vertical-align: middle;
        }
        table.boards-list th {
            font-size: 7px;
            background: #bfdbfe;
            color: #1e3a8a;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.boards-list .col-n { width: 4%; text-align: center; }
        table.boards-list .col-use { width: 42%; }
        table.boards-list .col-type { width: 18%; }
        table.boards-list .col-size { width: 16%; }
        table.boards-list .col-num { width: 10%; text-align: center; }
        table.boards-list td.piece-use { font-size: 8px; font-weight: bold; }
        .summary.tight { margin-top: 4px; padding: 4px 6px; font-size: 8px; }
        .summary.tight strong { font-size: 8px; }
        h2.tight { margin: 10px 0 4px; }
        table.pieces-list th, table.pieces-list td { padding: 3px 4px; font-size: 9px; }
        table.pieces-list .col-code { width: 8%; }
        table.pieces-list .col-name { width: 34%; }
        table.pieces-list .col-use { width: 34%; }
        table.pieces-list .col-num { width: 12%; }
        table.data.hanging-bars-table th {
            background: #bfdbfe;
            color: #1e3a8a;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.data.hanging-bars-list { table-layout: fixed; margin-top: 4px; width: 60%; }
        table.hanging-bars-list th, table.hanging-bars-list td {
            padding: 1px 4px;
            font-size: 8px;
            line-height: 1.15;
            vertical-align: middle;
        }
        table.hanging-bars-list th { font-size: 7px; padding: 2px 4px; }
        table.hanging-bars-list .col-code { width: 14%; }
        table.hanging-bars-list .col-name { width: 56%; }
        table.hanging-bars-list .col-num { width: 30%; }
        table.data.drawers-table th {
            background: #e9d5ff;
            color: #581c87;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.data.drawers-list { table-layout: fixed; margin-top: 4px; width: 60%; }
        table.drawers-list th, table.drawers-list td {
            padding: 1px 4px;
            font-size: 8px;
            line-height: 1.15;
            vertical-align: middle;
        }
        table.drawers-list th { font-size: 7px; padding: 2px 4px; }
        table.drawers-list .col-code { width: 12%; }
        table.drawers-list .col-name { width: 28%; }
        table.drawers-list .col-num { width: 20%; }
        .page-break { page-break-before: always; }
        .muted { color: #6b7280; font-size: 10px; }
        .muted.small { font-size: 8px; }
        .badge { display: inline-block; padding: 1px 6px; background: #e5e7eb; border-radius: 3px; font-size: 10px; }
        ul.materials { margin: 4px 0 0 16px; padding: 0; }
        .hl-type {
            font-weight: bold;
            font-size: 10px;
            color: #1d4ed8;
            background-color: #fef08a;
            padding: 2px 5px;
            display: inline-block;
            margin-bottom: 3px;
        }
        .lateral-use {
            font-weight: bold;
            font-size: 10px;
            color: #111827;
            margin: 2px 0 1px;
        }
        .lateral-detail {
            font-size: 9px;
            color: #4b5563;
            margin: 0 0 4px 0;
        }
    </style>
</head>
<body>
    <div class="running-header">
        <span class="hl-type">Tipo de mueble: {{ $furnitureType }}</span>
    </div>
    <h1>Plan de corte — {{ $title }}</h1>
    <div class="muted">Generado el {{ $generatedAt }}</div>

    <h2>1. Datos del mueble</h2>
    @php
        $drawersCount = (int) ($configuration['drawers_count'] ?? 0);
        $hangingBarsCount = (int) ($configuration['hanging_bars_count'] ?? 0);
        $hangingBarLengthCm = $configuration['hanging_bar_length_cm']
            ?? (isset($configuration['hanging_bar_measure_m'])
                ? ((float) $configuration['hanging_bar_measure_m'] * 100)
                : null);
    @endphp
    <table class="facts">
        <tr>
            <td>
                <div class="fact-label">Tipo de mueble</div>
                <div class="fact-value"><span class="hl-type">{{ $furnitureType }}</span></div>
            </td>
            <td>
                <div class="fact-label">Tipo de lado</div>
                <div class="fact-value"><span class="hl-type">{{ $sideDetail }}</span></div>
            </td>
            <td>
                <div class="fact-label">Módulos</div>
                <div class="fact-value">{{ $configuration['module_count'] ?? 1 }}</div>
                @if(!empty($interiorLaterals) && is_array($interiorLaterals))
                    <div class="fact-label" style="margin-top:4px;">Laterales interiores</div>
                    @foreach($interiorLaterals as $lateral)
                        <div class="fact-sub">{{ $lateral['use'] }} — {{ $lateral['board_mm'] }} mm — {{ measure_format($lateral['width_cm']) }} × {{ measure_format($lateral['height_cm']) }} cm</div>
                    @endforeach
                @elseif((int) ($configuration['interior_laterals_count'] ?? 0) > 0)
                    <div class="fact-sub">
                        {{ (int) $configuration['interior_laterals_count'] }} lateral(es), tablero {{ $configuration['interior_lateral_board_mm'] ?? 16 }} mm
                        @if(!empty($configuration['lateral_interior_depths_cm'][1]))
                            — alto {{ measure_format($configuration['lateral_interior_depths_cm'][1]) }} cm
                        @endif
                    </div>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <div class="fact-label">Hueco</div>
                <table class="measure-box">
                    <tr>
                        <th>Alto</th>
                        <th>Ancho</th>
                        <th>Fondo</th>
                    </tr>
                    <tr>
                        <td>{{ ($opening['height_m'] ?? null) ? $opening['height_m'].' m' : '—' }}</td>
                        <td>{{ ($opening['width_m'] ?? null) ? $opening['width_m'].' m' : '—' }}</td>
                        <td>{{ ($opening['depth_cm'] ?? null) ? measure_format((float) $opening['depth_cm']).' cm' : '—' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="fact-label">Medida global</div>
                <table class="measure-box">
                    <tr>
                        <th>Alto</th>
                        <th>Ancho</th>
                        <th>Fondo</th>
                    </tr>
                    <tr>
                        <td>{{ isset($global['height_cm']) ? measure_format($global['height_cm']).' cm' : '—' }}</td>
                        <td>{{ isset($global['width_cm']) ? measure_format($global['width_cm']).' cm' : '—' }}</td>
                        <td>{{ isset($global['depth_cm']) ? measure_format($global['depth_cm']).' cm' : '—' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="fact-label">Baldas</div>
                <div class="fact-value">{{ $configuration['shelf_count'] ?? 0 }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="fact-label">Tableros</div>
                <div class="fact-value">{{ $structureBoard['quantity'] ?? 0 }} × {{ $structureBoard['thickness_mm'] ?? 16 }} mm</div>
                @if(! empty($showBoardBase))
                    <table class="measure-box">
                        <tr>
                            <th>Ancho base</th>
                            <th>Alto base</th>
                        </tr>
                        <tr>
                            <td>{{ measure_format($boardSize['width_cm']) }} cm</td>
                            <td>{{ measure_format($boardSize['height_cm']) }} cm</td>
                        </tr>
                    </table>
                @endif
            </td>
            <td>
                <div class="fact-label">Fondo del mueble</div>
                <div class="fact-value">{{ $backBoard['quantity'] ?? 1 }} × {{ $backBoard['thickness_mm'] ?? 10 }} mm</div>
                <table class="measure-box">
                    <tr>
                        <th>Ancho</th>
                        <th>Alto</th>
                    </tr>
                    <tr>
                        <td>{{ isset($global['width_cm']) ? measure_format($global['width_cm']).' cm' : '—' }}</td>
                        <td>{{ isset($global['height_cm']) ? measure_format($global['height_cm']).' cm' : '—' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <div class="fact-label">Cajones</div>
                <div class="fact-value">
                    @if($drawersCount >= 1)
                        {{ $drawersCount }} {{ $drawersCount === 1 ? 'cajón' : 'cajones' }}
                    @else
                        —
                    @endif
                </div>
                @if($drawersCount >= 1)
                    <table class="measure-box">
                        <tr>
                            <th>Ancho</th>
                            <th>Alto</th>
                            <th>Fondo</th>
                        </tr>
                        <tr>
                            <td>{{ filled($configuration['drawer_width_cm'] ?? null) ? measure_format((float) $configuration['drawer_width_cm']).' cm' : '—' }}</td>
                            <td>{{ filled($configuration['drawer_height_cm'] ?? null) ? measure_format((float) $configuration['drawer_height_cm']).' cm' : '—' }}</td>
                            <td>{{ filled($configuration['drawer_depth_cm'] ?? null) ? measure_format((float) $configuration['drawer_depth_cm']).' cm' : '—' }}</td>
                        </tr>
                    </table>
                @endif
                <div class="fact-label" style="margin-top:4px;">Barras de colgar</div>
                <div class="fact-value">
                    @if($hangingBarsCount >= 1)
                        {{ $hangingBarsCount }} {{ $hangingBarsCount === 1 ? 'barra' : 'barras' }}
                    @else
                        —
                    @endif
                </div>
                @if($hangingBarsCount >= 1)
                    <table class="measure-box">
                        <tr>
                            <th>Largo</th>
                        </tr>
                        <tr>
                            <td>{{ filled($hangingBarLengthCm) && (float) $hangingBarLengthCm > 0 ? measure_format((float) $hangingBarLengthCm).' cm' : '—' }}</td>
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    <table class="sheet-split">
        <tr>
            <td class="sheet-main">
    <h2>2. Materiales seleccionados</h2>
    @if(count($materials))
        <p style="margin:0 0 6px;"><strong>Material:</strong> {{ implode(' · ', $materials) }}</p>
    @endif
    @if(!empty($antRemnantMatches ?? []))
        <p style="margin:0 0 6px;color:#9a3412;font-weight:bold;font-size:9px;">
            Prioridad almacén (ID S-XX): piezas del listado Sobrantes Muebles Ant.
            Cada fila indica si es «Pieza registrada» o «Sobrante de mueble (sistema)».
        </p>
    @endif

    <table class="data boards-list">
        <thead>
            <tr>
                <th class="col-n">#</th>
                <th class="col-type">No. tablero</th>
                <th class="col-use">Para qué utilizarlo?</th>
                <th class="col-size">Medidas (cm)</th>
                <th class="col-num">Grosor</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($materialUsageRows ?? []) as $row)
                <tr @if(!empty($row['from_ant'])) style="background:#ffedd5;" @endif>
                    <td class="col-n">{{ $loop->iteration }}</td>
                    <td class="col-type">
                        <strong>{{ $row['name'] }}</strong>
                        @if(!empty($row['from_ant']) && !empty($row['origin']))
                            <div style="font-size:8px;font-weight:bold;color:#9a3412;margin-top:1px;">
                                Origen: {{ $row['origin'] }}
                            </div>
                        @endif
                    </td>
                    <td class="col-use piece-use">{{ $row['use'] }}</td>
                    <td class="col-size measure">{{ $row['measures'] }}</td>
                    <td class="col-num">{{ $row['thickness'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Sin materiales ni piezas calculadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <h2 class="tight">3. Listado de tableros a usar</h2>
    <table class="data boards-list">
        <thead>
            <tr>
                <th class="col-n">#</th>
                <th class="col-type">No. tablero</th>
                <th class="col-use">Para qué utilizarlo?</th>
                <th class="col-size">Medidas (cm)</th>
                <th class="col-num">Piezas</th>
                <th class="col-num">Sobr.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($boardsToUse as $board)
                <tr>
                    <td class="col-n">{{ $loop->iteration }}</td>
                    <td class="col-type"><strong>{{ $board['name'] ?? $board['type'] }}</strong></td>
                    <td class="col-use piece-use">{{ $board['use'] ?? '—' }}</td>
                    <td class="col-size measure">{{ measure_format($board['width_cm']) }}×{{ measure_format($board['height_cm']) }}</td>
                    <td class="col-num">{{ $board['quantity'] ?? $board['pieces_count'] }}</td>
                    <td class="col-num">{{ $board['remnants_count'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Sin tableros calculados. Completa las medidas globales.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary tight">
        <strong>Resumen:</strong>
        Tableros {{ $totals['main_boards'] }}
        + fondo {{ $totals['fondo_count'] ?? $totals['back_boards'] ?? 1 }}
        @if(($totals['back_stock_boards'] ?? 0) > ($totals['fondo_count'] ?? 1))
            ({{ $totals['back_stock_boards'] }} hojas de corte)
        @endif
        = <strong>{{ $totals['total_boards'] }}</strong>
        | Eff. {{ measure_format($totals['efficiency_percent']) }}%
        | Usada {{ measure_format($totals['used_area_cm2']) }} cm²
        | Desp. {{ measure_format($totals['waste_area_cm2']) }} cm²
    </div>
            </td>
            <td class="sheet-draw">
                @if(!empty($elevation))
                    <div class="caption">ALZADO DEL MUEBLE</div>
                    <img src="{{ $elevation }}" alt="Alzado del mueble">
                @endif
            </td>
        </tr>
    </table>

    <div class="page-break">
    <h2>4. Piezas a usar (con medidas)</h2>
    <table class="data pieces-list">
        <thead>
            <tr>
                <th class="col-code">Código</th>
                <th class="col-name">Nombre de la pieza</th>
                <th class="col-use">Para qué se usa</th>
                <th class="col-num">Ancho (cm)</th>
                <th class="col-num">Alto (cm)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($namedPieces as $piece)
                <tr>
                    <td class="col-code">{{ $piece['code'] }}</td>
                    <td class="col-name"><strong>{{ $piece['name'] }}</strong></td>
                    <td class="col-use piece-use">{{ $piece['use'] ?? '—' }}</td>
                    <td class="col-num measure">{{ measure_format($piece['width_cm']) }}</td>
                    <td class="col-num measure">{{ measure_format($piece['height_cm']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Sin piezas calculadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

        <h2 class="section-tight">5. Piezas sobrantes / remanentes</h2>
        <table class="data remnants-list">
            <thead>
                <tr>
                    <th class="col-name">Nombre</th>
                    <th class="col-board">Tablero origen</th>
                    <th class="col-num">Ancho (cm)</th>
                    <th class="col-num">Alto (cm)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($namedRemnants as $remnant)
                    <tr>
                        <td class="col-name remnant-code">{{ $remnant['name'] }}</td>
                        <td class="col-board">{{ $remnant['board'] }}</td>
                        <td class="col-num measure">{{ measure_format($remnant['width_cm']) }}</td>
                        <td class="col-num measure">{{ measure_format($remnant['height_cm']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Sin remanentes registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @php
            $hasDrawers = !empty($drawers ?? []) && count($drawers) > 0;
            $hasHangingBars = !empty($hangingBars ?? []) && count($hangingBars) > 0;
            $accessorySection = 6;
        @endphp

        @if($hasDrawers)
            <h2 class="section-tight">{{ $accessorySection }}. Cajones</h2>
            <table class="data drawers-list drawers-table">
                <thead>
                    <tr>
                        <th class="col-code">Código</th>
                        <th class="col-name">Nombre</th>
                        <th class="col-num">Ancho (cm)</th>
                        <th class="col-num">Alto (cm)</th>
                        <th class="col-num">Profundidad (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($drawers as $drawer)
                        <tr>
                            <td class="col-code">{{ $drawer['code'] }}</td>
                            <td class="col-name"><strong>{{ $drawer['name'] }}</strong></td>
                            <td class="col-num measure">
                                {{ isset($drawer['width_cm']) ? measure_format($drawer['width_cm']) : '—' }}
                            </td>
                            <td class="col-num measure">
                                {{ isset($drawer['height_cm']) ? measure_format($drawer['height_cm']) : '—' }}
                            </td>
                            <td class="col-num measure">
                                {{ isset($drawer['depth_cm']) ? measure_format($drawer['depth_cm']) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @php $accessorySection++; @endphp
        @endif

        @if($hasHangingBars)
            <h2 class="section-tight">{{ $accessorySection }}. Barras de colgar</h2>
            <table class="data hanging-bars-list hanging-bars-table">
                <thead>
                    <tr>
                        <th class="col-code">Código</th>
                        <th class="col-name">Nombre</th>
                        <th class="col-num">Longitud (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hangingBars as $bar)
                        <tr>
                            <td class="col-code">{{ $bar['code'] }}</td>
                            <td class="col-name"><strong>{{ $bar['name'] }}</strong></td>
                            <td class="col-num measure">
                                {{ isset($bar['length_cm']) ? measure_format($bar['length_cm']) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @php $accessorySection++; @endphp
        @endif
    </div>

    @php
        $cutSectionNumber = $accessorySection;
        $backSectionNumber = $cutSectionNumber + 1;
    @endphp

    @foreach(($structureBoardPages ?? []) as $pageDiagrams)
        <div class="board-page">
            @if($loop->first)
                <h2>{{ $cutSectionNumber }}. Cortes de tablero ({{ $structureBoard['thickness_mm'] ?? 16 }} mm)</h2>
            @endif

            <table class="boards-pair">
                <tr>
                    @foreach($pageDiagrams as $diagram)
                        <td>
                            <div class="board-block">
                                @php
                                    $boardNum = $diagram['board']['index']
                                        ?? $diagram['index']
                                        ?? $loop->iteration;
                                @endphp
                                <h3>
                                    <span class="board-badge">TABLERO {{ $boardNum }}</span>
                                    ({{ $structureBoard['thickness_mm'] ?? 16 }} mm)
                                    — {{ measure_format($diagram['width_cm']) }} × {{ measure_format($diagram['height_cm']) }} cm
                                </h3>
                                <div class="board-use">Uso: {{ $diagram['board']['use'] ?? '—' }}</div>
                                @if(!empty($diagram['board']['remnants']))
                                    <div class="remnant-inline-legend">
                                        @foreach($diagram['board']['remnants'] as $remnant)
                                            <span class="remnant-inline-item">
                                                {{ $loop->iteration }}. {{ $remnant['label'] ?? 'S-00' }}
                                                — {{ measure_format($remnant['width']) }} x {{ measure_format($remnant['height']) }} cm
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="diagram">
                                    <img src="{{ $diagram['image'] }}" alt="{{ $diagram['board']['title'] ?? 'Tablero' }}">
                                </div>
                                @php $board = $diagram['board'] ?? null; @endphp
                                @if($board)
                                    <table class="data compact pieces-table">
                                        <thead>
                                            <tr>
                                                <th>Pieza</th>
                                                <th>Para qué se usa</th>
                                                <th>Medidas</th>
                                                <th>Posición (x,y)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($board['placements'] ?? []) as $placement)
                                                <tr>
                                                    <td>{{ $placement['label'] }}</td>
                                                    <td class="piece-use">{{ $placement['use'] ?? '—' }}</td>
                                                    <td class="measure">{{ measure_format($placement['width']) }}×{{ measure_format($placement['height']) }}</td>
                                                    <td class="measure">{{ measure_format($placement['x']) }},{{ measure_format($placement['y']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if(!empty($board['remnants']))
                                        <table class="data compact remnants-table">
                                            <thead>
                                                <tr>
                                                    <th>Sobrante</th>
                                                    <th>Para qué se usa</th>
                                                    <th>Medidas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($board['remnants'] as $remnant)
                                                    <tr>
                                                        <td class="remnant-code">{{ $remnant['label'] ?? 'S-00' }}</td>
                                                        <td class="piece-use">Sobrante / remanente</td>
                                                        <td class="measure">{{ measure_format($remnant['width']) }}×{{ measure_format($remnant['height']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endforeach

    @foreach(($backBoardPages ?? []) as $pageDiagrams)
        <div class="board-page">
            @if($loop->first)
                <h2>{{ $backSectionNumber }}. Corte del fondo del mueble ({{ $backBoard['thickness_mm'] ?? 10 }} mm)</h2>
                <p class="board-use">Uso: 1 tablero normal — {{ $backBoard['quantity'] ?? 1 }} fondo(s) — {{ isset($global['width_cm']) ? measure_format($global['width_cm']) : '—' }} × {{ isset($global['height_cm']) ? measure_format($global['height_cm']) : '—' }} cm (1 corte)</p>
                <p class="muted">Vista horizontal del tablero. Diagrama sobre tablero completo {{ measure_format($boardSize['width_cm']) }} × {{ measure_format($boardSize['height_cm']) }} cm.</p>
            @endif

            <table class="boards-pair">
                <tr>
                    @foreach($pageDiagrams as $diagram)
                        <td>
                            <div class="board-block">
                                @php
                                    $boardNum = $diagram['board']['index']
                                        ?? $diagram['index']
                                        ?? $loop->iteration;
                                @endphp
                                <h3>
                                    <span class="board-badge">TABLERO {{ $boardNum }}</span>
                                    ({{ $backBoard['thickness_mm'] ?? 10 }} mm)
                                    — {{ measure_format($diagram['width_cm']) }} × {{ measure_format($diagram['height_cm']) }} cm
                                </h3>
                                <div class="board-use">{{ $diagram['board']['use'] ?? 'Uso: fondo del mueble (1 corte sobre 1 tablero normal)' }}</div>
                                @if(!empty($diagram['board']['remnants']))
                                    <div class="remnant-inline-legend">
                                        @foreach($diagram['board']['remnants'] as $remnant)
                                            <span class="remnant-inline-item">
                                                {{ $loop->iteration }}. {{ $remnant['label'] ?? 'S-00' }}
                                                — {{ measure_format($remnant['width']) }} x {{ measure_format($remnant['height']) }} cm
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="diagram">
                                    <img src="{{ $diagram['image'] }}" alt="Fondo del mueble">
                                </div>
                                @php $board = $diagram['board'] ?? null; @endphp
                                @if($board)
                                    <table class="data compact pieces-table">
                                        <thead>
                                            <tr>
                                                <th>Pieza</th>
                                                <th>Para qué se usa</th>
                                                <th>Medidas</th>
                                                <th>Posición (x,y)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($board['placements'] ?? []) as $placement)
                                                <tr>
                                                    <td>{{ $placement['label'] }}</td>
                                                    <td class="piece-use">{{ $placement['use'] ?? '—' }}</td>
                                                    <td class="measure">{{ measure_format($placement['width']) }}×{{ measure_format($placement['height']) }}</td>
                                                    <td class="measure">{{ measure_format($placement['x']) }},{{ measure_format($placement['y']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if(!empty($board['remnants']))
                                        <table class="data compact remnants-table">
                                            <thead>
                                                <tr>
                                                    <th>Sobrante</th>
                                                    <th>Para qué se usa</th>
                                                    <th>Medidas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($board['remnants'] as $remnant)
                                                    <tr>
                                                        <td class="remnant-code">{{ $remnant['label'] ?? 'S-00' }}</td>
                                                        <td class="piece-use">Sobrante / remanente</td>
                                                        <td class="measure">{{ measure_format($remnant['width']) }}×{{ measure_format($remnant['height']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
