<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Plan de corte — {{ $project->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin-top: 24px; margin-bottom: 8px; }
        .meta { margin-bottom: 16px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .diagram { margin-top: 16px; text-align: center; }
        .summary { margin-top: 12px; }
    </style>
</head>
<body>
    <h1>Plan de corte — {{ $project->name }}</h1>
    <div class="meta">
        <div><strong>Referencia:</strong> {{ $project->reference ?: '—' }}</div>
        <div><strong>Medidas mueble:</strong> {{ $project->height_cm }} × {{ $project->width_cm }} × {{ $project->depth_cm }} cm (alto × ancho × fondo)</div>
        <div><strong>Ubicación:</strong> {{ $project->placement_type->getLabel() }}</div>
        <div><strong>Ancho efectivo:</strong> {{ $project->effective_width_cm }} cm</div>
        <div><strong>Puertas:</strong> {{ $project->door_count }} ({{ $project->door_type->getLabel() }})</div>
        @if($project->material)
            <div><strong>Material:</strong> {{ $project->material->name }} — {{ $project->material->dimensions_label }}</div>
        @endif
    </div>

    <h2>Piezas requeridas</h2>
    <table>
        <thead>
            <tr>
                <th>Pieza</th>
                <th>Ancho (cm)</th>
                <th>Alto (cm)</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pieces as $piece)
                <tr>
                    <td>{{ $piece['label'] }}</td>
                    <td>{{ measure_format($piece['width_cm']) }}</td>
                    <td>{{ measure_format($piece['height_cm']) }}</td>
                    <td>{{ $piece['quantity'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if(!empty($cutPlan['total_boards']))
        <div class="summary">
            <strong>Tableros necesarios:</strong> {{ $cutPlan['total_boards'] }} |
            <strong>Eficiencia:</strong> {{ $cutPlan['efficiency_percent'] }}% |
            <strong>Desperdicio:</strong> {{ measure_format($cutPlan['waste_area_cm2']) }} cm²
        </div>
    @endif

    <h2>Diagrama de cortes</h2>
    <div class="diagram">
        {!! $svg !!}
    </div>
</body>
</html>
