@if (filled($furnitureTypeLabel ?? null))
    <div
        class="mb-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-950/50"
        style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:nowrap;"
    >
        <div style="min-width:0;">
            <div class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-400">
                Tipo de mueble
            </div>
            <div class="mt-0.5 text-base font-bold text-blue-600 dark:text-blue-400">
                {{ $furnitureTypeLabel }}
            </div>
        </div>

        @if (! empty($showTotalShelves))
            <div style="display:flex;align-items:baseline;gap:0.5rem;white-space:nowrap;flex-shrink:0;">
                <span style="font-size:22px;font-weight:700;">Total baldas:</span>
                <span style="font-size:22px;font-weight:700;color:#dc2626;animation:shelves-blink 1s step-start infinite;">
                    {{ (int) ($totalShelves ?? 0) }}
                </span>
            </div>
            <style>
                @keyframes shelves-blink {
                    0%, 49% { opacity: 1; }
                    50%, 100% { opacity: 0; }
                }
            </style>
        @endif
    </div>
@endif
