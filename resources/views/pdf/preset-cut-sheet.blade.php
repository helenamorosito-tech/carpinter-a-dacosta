<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.35; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        h3 { font-size: 12px; margin: 14px 0 6px; }
        .meta { color: #4b5563; margin-bottom: 10px; }
        .meta div { margin-bottom: 2px; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; width: 50%; padding-right: 8px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; }
        table.data th { background: #f3f4f6; font-size: 10px; }
        .summary { margin-top: 8px; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; }
        .diagram { margin-top: 10px; text-align: center; page-break-inside: avoid; }
        .page-break { page-break-before: always; }
        .muted { color: #6b7280; font-size: 10px; }
        .badge { display: inline-block; padding: 1px 6px; background: #e5e7eb; border-radius: 3px; font-size: 10px; }
        ul.materials { margin: 4px 0 0 16px; padding: 0; }
    </style>
</head>
<body>
    <h1>Plan de corte — {{ $title }}</h1>
    <div class="muted">Generado el {{ $generatedAt }}</div>

    <h2>1. Datos del mueble</h2>
    <table class="grid">
        <tr>
            <td>
                <div class="meta">
                    <div><strong>Tipo de mueble:</strong> {{ $furnitureType }}</div>
                    <div><strong>Tipo de lado:</strong> {{ $sideDetail }}</div>
                    <div>
                        <strong>Hueco:</strong>
                        @if($opening['height_m'] || $opening['width_m'] || $opening['depth_cm'])
                            {{ $opening['height_m'] ?? '—' }} m × {{ $opening['width_m'] ?? '—' }} m × {{ $opening['depth_cm'] ?? '—' }} cm
                        @else
                            —
                        @endif
                    </div>
                    <div>
                        <strong>Medida global:</strong>
                        {{ $global['height_cm'] ?? '—' }} × {{ $global['width_cm'] ?? '—' }} × {{ $global['depth_cm'] ?? '—' }} cm
                        (alto × ancho × profundidad)
                    </div>
                </div>
            </td>
            <td>
                <div class="meta">
                    <div><strong>Tablero base:</strong> {{ number_format((float) $boardSize['width_cm'], 2) }} × {{ number_format((float) $boardSize['height_cm'], 2) }} cm</div>
                    <div><strong>Tableros de fondo:</strong> {{ $backBoard['quantity'] }} ud. × {{ $backBoard['thickness_mm'] }} mm</div>
                    <div><strong>Módulos:</strong> {{ $configuration['module_count'] ?? 1 }}</div>
                    <div><strong>Baldas:</strong> {{ $configuration['shelf_count'] ?? 0 }}</div>
                    @if(!empty($configuration['trunk_shelf_length_cm']) || !empty($configuration['trunk_shelf_depth_cm']))
                        <div>
                            <strong>Balda maletero:</strong>
                            {{ $configuration['trunk_shelf_length_cm'] ?? '—' }} × {{ $configuration['trunk_shelf_depth_cm'] ?? '—' }} cm
                            (tablero {{ $configuration['trunk_shelf_board_mm'] ?? 16 }} mm)
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <h2>2. Materiales seleccionados</h2>
    @if(count($materials))
        <ul class="materials">
            @foreach($materials as $material)
                <li>{{ $material }}</li>
            @endforeach
        </ul>
    @else
        <p class="muted">Sin materiales seleccionados.</p>
    @endif

    <h2>3. Listado de tableros a usar</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Tablero</th>
                <th>Tipo</th>
                <th>Medidas (cm)</th>
                <th>Piezas / uds.</th>
                <th>Sobrantes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($boardsToUse as $board)
                <tr>
                    <td>{{ $board['name'] }}</td>
                    <td>{{ $board['type'] }}</td>
                    <td>{{ number_format((float) $board['width_cm'], 2) }} × {{ number_format((float) $board['height_cm'], 2) }}</td>
                    <td>{{ $board['quantity'] ?? $board['pieces_count'] }}</td>
                    <td>{{ $board['remnants_count'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Sin tableros calculados. Completa las medidas globales.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <strong>Resumen:</strong>
        Tableros principales {{ $totals['main_boards'] }}
        + fondos {{ $totals['back_boards'] }}
        = <strong>{{ $totals['total_boards'] }}</strong> tableros |
        Eficiencia {{ number_format((float) $totals['efficiency_percent'], 1) }}% |
        Área usada {{ number_format((float) $totals['used_area_cm2'], 0) }} cm² |
        Desperdicio {{ number_format((float) $totals['waste_area_cm2'], 0) }} cm²
    </div>

    <h2>4. Piezas a usar (con medidas)</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre de la pieza</th>
                <th>Ancho (cm)</th>
                <th>Alto (cm)</th>
                <th>Área (cm²)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($namedPieces as $piece)
                <tr>
                    <td>{{ $piece['code'] }}</td>
                    <td>{{ $piece['name'] }}</td>
                    <td>{{ number_format((float) $piece['width_cm'], 2) }}</td>
                    <td>{{ number_format((float) $piece['height_cm'], 2) }}</td>
                    <td>{{ number_format((float) $piece['area_cm2'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Sin piezas calculadas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>5. Piezas sobrantes / remanentes</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tablero origen</th>
                <th>Ancho (cm)</th>
                <th>Alto (cm)</th>
                <th>Posición (x, y)</th>
                <th>Área (cm²)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($namedRemnants as $remnant)
                <tr>
                    <td>{{ $remnant['name'] }}</td>
                    <td>{{ $remnant['board'] }}</td>
                    <td>{{ number_format((float) $remnant['width_cm'], 2) }}</td>
                    <td>{{ number_format((float) $remnant['height_cm'], 2) }}</td>
                    <td>{{ number_format((float) $remnant['x'], 1) }}, {{ number_format((float) $remnant['y'], 1) }}</td>
                    <td>{{ number_format((float) $remnant['area_cm2'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Sin remanentes registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>6. Dibujos de tableros con cortes</h2>
    <p class="muted">Mismos gráficos que en el panel: cada rectángulo de color es una pieza y las zonas grises discontinuas son sobrantes.</p>

    <h3>Diagrama completo (igual que en el recurso)</h3>
    <div class="diagram">
        {!! $svg !!}
    </div>

    @foreach($boardDiagrams as $diagram)
        <div class="page-break"></div>
        <h3>Tablero {{ $diagram['index'] }} — {{ number_format((float) $diagram['width_cm'], 2) }} × {{ number_format((float) $diagram['height_cm'], 2) }} cm</h3>
        @php
            $board = collect($cutPlan['boards'] ?? [])->firstWhere('index', $diagram['index']);
        @endphp
        @if($board)
            <table class="data">
                <thead>
                    <tr>
                        <th>Piezas en este tablero</th>
                        <th>Medidas (cm)</th>
                        <th>Posición (x, y)</th>
                        <th>Rotada</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($board['placements'] ?? []) as $placement)
                        <tr>
                            <td>{{ $placement['label'] }}</td>
                            <td>{{ number_format((float) $placement['width'], 2) }} × {{ number_format((float) $placement['height'], 2) }}</td>
                            <td>{{ number_format((float) $placement['x'], 1) }}, {{ number_format((float) $placement['y'], 1) }}</td>
                            <td>{{ !empty($placement['rotated']) ? 'Sí' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if(!empty($board['remnants']))
                <p class="muted" style="margin-top:6px;">
                    Sobrantes:
                    @foreach($board['remnants'] as $remnant)
                        {{ $remnant['label'] ?? 'Sobrante' }}
                        ({{ number_format((float) $remnant['width'], 2) }}×{{ number_format((float) $remnant['height'], 2) }} cm)@if(!$loop->last), @endif
                    @endforeach
                </p>
            @endif
        @endif
        <div class="diagram">
            {!! $diagram['svg'] !!}
        </div>
    @endforeach
</body>
</html>
