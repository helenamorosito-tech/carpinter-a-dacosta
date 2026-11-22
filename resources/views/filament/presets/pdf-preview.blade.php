<div class="flex h-[85vh] flex-col gap-3">
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Visor del plan de corte. Puedes hacer zoom, imprimir o descargar desde aquí.
        </p>
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ $previewUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="fi-btn relative grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-950 outline-none transition hover:bg-gray-200 dark:bg-white/10 dark:text-white dark:hover:bg-white/20"
            >
                Abrir a pantalla completa
            </a>
            <a
                href="{{ $downloadUrl }}"
                class="fi-btn relative grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white outline-none transition hover:bg-amber-400"
            >
                Descargar PDF
            </a>
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-950">
        <iframe
            title="Previsualización del plan de corte"
            src="{{ $previewUrl }}"
            class="h-full w-full"
        ></iframe>
    </div>
</div>
