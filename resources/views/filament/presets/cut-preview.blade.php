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
@endphp

<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Tableros principales</div>
            <div class="mt-1 text-2xl font-semibold">{{ $result['calculated_boards_count'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Tableros de fondo</div>
            <div class="mt-1 text-2xl font-semibold">{{ $result['back_board_quantity'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Total necesarios</div>
            <div class="mt-1 text-2xl font-semibold">{{ $result['total_boards_needed'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-xs uppercase tracking-wide text-gray-500">Eficiencia</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format((float) ($cutPlan['efficiency_percent'] ?? 0), 1) }}%</div>
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
                            <td class="px-4 py-2">{{ number_format($piece['width_cm'], 2) }}</td>
                            <td class="px-4 py-2">{{ number_format($piece['height_cm'], 2) }}</td>
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
                    <div class="mb-2 text-sm font-medium">Tablero {{ $board['index'] }}</div>
                    @if(($board['remnants'] ?? []) === [])
                        <p class="text-sm text-gray-500">Sin remanente usable</p>
                    @else
                        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
                            @foreach($board['remnants'] as $remnant)
                                <li>
                                    {{ $remnant['label'] ?? 'Sobrante' }}:
                                    {{ number_format($remnant['width'], 2) }} × {{ number_format($remnant['height'], 2) }} cm
                                    (x={{ number_format($remnant['x'], 1) }}, y={{ number_format($remnant['y'], 1) }})
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
                    Área usada: {{ number_format((float) ($cutPlan['used_area_cm2'] ?? 0), 0) }} cm² —
                    Desperdicio: {{ number_format((float) ($cutPlan['waste_area_cm2'] ?? 0), 0) }} cm²
                </p>
            @endif
        </div>
    </div>
</div>
