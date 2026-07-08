@php
    $pieces = $getState() ?? [];
@endphp

<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
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
