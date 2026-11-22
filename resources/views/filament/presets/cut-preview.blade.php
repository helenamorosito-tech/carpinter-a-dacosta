@php
    $result = $result ?? [
        'pieces' => [],
        'cut_plan' => ['boards' => [], 'total_boards' => 0, 'used_area_cm2' => 0, 'waste_area_cm2' => 0, 'efficiency_percent' => 0],
        'svg_diagram' => '',
        'calculated_boards_count' => 0,
        'back_board_quantity' => 0,
        'total_boards_needed' => 0,
    ];
    $pieces = $result['pieces'] ?? [];
    $cutPlan = $result['cut_plan'] ?? [];
    $boards = $cutPlan['boards'] ?? [];
    $antMatches = $antMatches ?? [];
    $antCount = count($antMatches);
    $newBoards = (int) ($result['total_boards_needed'] ?? 0);
    $structureBoards = (int) ($result['calculated_boards_count'] ?? 0);
    $backBoards = (int) ($result['back_board_quantity'] ?? $result['fondo_count'] ?? 0);
@endphp

<div class="space-y-6">
    {{-- Resumen de aprovechamiento --}}
    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/30">
        <h3 class="text-sm font-semibold text-orange-950 dark:text-orange-100">
            Aprovechamiento de materiales
        </h3>
        <p class="mt-1 text-sm text-orange-900/90 dark:text-orange-200/90">
            @if($antCount > 0)
                Vamos a usar
                <strong>{{ $antCount }} {{ $antCount === 1 ? 'pieza sobrante' : 'piezas sobrantes' }}</strong>
                del almacén
                ({{ collect($antMatches)->pluck('code')->unique()->implode(', ') }})
                y
                <strong>{{ $newBoards }} {{ $newBoards === 1 ? 'tablero nuevo' : 'tableros nuevos' }}</strong>
                ({{ $structureBoards }} estructura + {{ $backBoards }} fondo).
            @else
                No hay sobrantes aprovechables en el listado (S-XX) para estas medidas y grosores.
                Se usarán
                <strong>{{ $newBoards }} {{ $newBoards === 1 ? 'tablero nuevo' : 'tableros nuevos' }}</strong>
                ({{ $structureBoards }} estructura + {{ $backBoards }} fondo).
            @endif
        </p>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-orange-200/80 bg-white/80 px-3 py-3 dark:border-orange-800/60 dark:bg-gray-900/60">
                <div class="text-xs uppercase tracking-wide text-orange-800/80 dark:text-orange-300/80">Sobrantes almacén</div>
                <div class="mt-1 text-2xl font-semibold text-orange-950 dark:text-orange-50">{{ $antCount }}</div>
                <div class="mt-0.5 text-xs text-orange-800/70 dark:text-orange-300/70">IDs S-XX registrados o de sistema</div>
            </div>
            <div class="rounded-lg border border-orange-200/80 bg-white/80 px-3 py-3 dark:border-orange-800/60 dark:bg-gray-900/60">
                <div class="text-xs uppercase tracking-wide text-orange-800/80 dark:text-orange-300/80">Tableros nuevos</div>
                <div class="mt-1 text-2xl font-semibold text-orange-950 dark:text-orange-50">{{ $newBoards }}</div>
                <div class="mt-0.5 text-xs text-orange-800/70 dark:text-orange-300/70">Estructura + fondo</div>
            </div>
            <div class="rounded-lg border border-orange-200/80 bg-white/80 px-3 py-3 dark:border-orange-800/60 dark:bg-gray-900/60">
                <div class="text-xs uppercase tracking-wide text-orange-800/80 dark:text-orange-300/80">Plan combinado</div>
                <div class="mt-1 text-lg font-semibold text-orange-950 dark:text-orange-50">
                    {{ $antCount }} sobr. + {{ $newBoards }} tabl.
                </div>
                <div class="mt-0.5 text-xs text-orange-800/70 dark:text-orange-300/70">Lo mismo irá en el PDF</div>
            </div>
        </div>
    </div>

    @if($antCount > 0)
        <div class="rounded-xl border border-orange-200 bg-white dark:border-orange-900/40 dark:bg-gray-900">
            <div class="border-b border-orange-100 px-4 py-3 dark:border-orange-900/40">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Piezas de sobrante a usar
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Localízalas en almacén por ID. Origen: pieza registrada o sobrante de mueble (sistema).
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-orange-50/80 dark:border-gray-700 dark:bg-orange-950/40">
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Origen</th>
                            <th class="px-4 py-2 text-left">Nombre</th>
                            <th class="px-4 py-2 text-left">Medidas</th>
                            <th class="px-4 py-2 text-left">Grosor</th>
                            <th class="px-4 py-2 text-left">Para pieza del mueble</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($antMatches as $match)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-4 py-2 font-semibold">{{ $match['code'] ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex rounded-md bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-900 dark:bg-orange-900/50 dark:text-orange-100">
                                        {{ $match['origin'] ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $match['remnant']->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $match['measures'] ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $match['thickness'] ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $match['piece_label'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Estructura ({{ $result['structure_board_thickness_mm'] ?? 16 }} mm)</div>
            <div class="mt-1 text-2xl font-semibold">{{ $structureBoards }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Fondo del mueble ({{ $result['back_board_thickness_mm'] ?? 10 }} mm)</div>
            <div class="mt-1 text-2xl font-semibold">{{ $backBoards }}</div>
            <div class="mt-1 text-xs text-gray-500">
                @php
                    $fondo = ($result['back_pieces'][0] ?? null);
                @endphp
                @if($fondo)
                    {{ measure_format((float) $fondo['width_cm']) }} × {{ measure_format((float) $fondo['height_cm']) }} cm
                    · corte en tablero 122×244
                @else
                    ancho × alto globales
                @endif
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Total tableros nuevos</div>
            <div class="mt-1 text-2xl font-semibold">{{ $newBoards }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Eficiencia</div>
            <div class="mt-1 text-2xl font-semibold">{{ measure_format((float) ($cutPlan['efficiency_percent'] ?? 0)) }}%</div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Diagrama de cortes</h3>
        <div class="overflow-x-auto">
            {!! $result['svg_diagram'] !!}
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Piezas que salen</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                        <th class="px-4 py-2 text-left">Pieza</th>
                        <th class="px-4 py-2 text-left">Ancho (cm)</th>
                        <th class="px-4 py-2 text-left">Alto (cm)</th>
                        <th class="px-4 py-2 text-left">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pieces as $piece)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-4 py-2">{{ $piece['label'] }}</td>
                            <td class="px-4 py-2">{{ measure_format($piece['width_cm']) }}</td>
                            <td class="px-4 py-2">{{ measure_format($piece['height_cm']) }}</td>
                            <td class="px-4 py-2">{{ $piece['quantity'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">Sin piezas calculadas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Remanente por tablero</h3>
        </div>
        <div class="space-y-4 p-4">
            @forelse($boards as $board)
                <div>
                    <div class="mb-2 text-sm font-medium">{{ $board['title'] ?? ('Tablero '.$board['index']) }}</div>
                    @if(($board['remnants'] ?? []) === [])
                        <p class="text-sm text-gray-500">Sin remanente usable</p>
                    @else
                        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
                            @foreach($board['remnants'] as $remnant)
                                <li>
                                    {{ $remnant['label'] ?? 'Sobrante' }}:
                                    {{ measure_format($remnant['width']) }} × {{ measure_format($remnant['height']) }} cm
                                    (x={{ measure_format($remnant['x']) }}, y={{ measure_format($remnant['y']) }})
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">Introduce medidas globales y material para ver el remanente.</p>
            @endforelse
            @if(!empty($cutPlan))
                <p class="text-xs text-gray-500">
                    Área usada: {{ measure_format((float) ($cutPlan['used_area_cm2'] ?? 0)) }} cm² —
                    Desperdicio: {{ measure_format((float) ($cutPlan['waste_area_cm2'] ?? 0)) }} cm²
                </p>
            @endif
        </div>
    </div>
</div>
