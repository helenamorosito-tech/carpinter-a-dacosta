<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 12px 14px 14px 14px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout td { vertical-align: top; }
        td.parts { width: 57%; padding-right: 8px; }
        td.drawing { width: 43%; }
        h1 { font-size: 12px; margin: 0 0 3px; letter-spacing: 0.2px; }
        .module-line { font-size: 11px; font-weight: bold; margin: 0 0 3px; }
        .module-line span { font-weight: normal; }
        table.hueco, table.armario { border-collapse: collapse; margin-bottom: 3px; }
        table.hueco th, table.hueco td, table.armario th, table.armario td {
            border: 1px solid #111;
            padding: 2px 6px;
            text-align: center;
            font-size: 10px;
        }
        table.hueco th, table.armario th { font-size: 8px; font-weight: bold; }
        .caption { font-size: 9px; font-weight: bold; margin: 0 0 2px; }
        .zocalo { font-weight: bold; }
        .units { color: #b91c1c; font-weight: bold; font-size: 14px; }
        .material { margin: 3px 0 3px; }
        .material .tag {
            display: inline-block;
            border: 1px solid #111;
            padding: 1px 6px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 4px;
        }
        .material .name {
            display: inline-block;
            background: #c5dd8a;
            border: 1px solid #6b8f3a;
            padding: 1px 8px;
            font-weight: bold;
            font-size: 11px;
        }
        table.list { width: 100%; border-collapse: collapse; }
        table.list td { padding: 0 2px 0 0; font-size: 9px; line-height: 1.2; vertical-align: baseline; }
        td.nm { font-weight: bold; width: 38%; }
        td.qt { width: 16%; }
        td.ms { font-weight: bold; }
        h2.block {
            font-size: 10px;
            margin: 5px 0 1px;
            color: #5b21b6;
            letter-spacing: 0.3px;
        }
        .frentes {
            margin-top: 5px;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: bold;
        }
        .cajonera { margin: 1px 0 2px; }
        .cajonera .num { color: #1d4ed8; font-size: 16px; font-weight: bold; }
        .cajonera .name { color: #b91c1c; font-size: 12px; font-weight: bold; }
        .drawer-line { font-size: 10px; margin: 0 0 1px; }
        .muted { color: #4b5563; font-size: 8px; margin-top: 3px; }
        .drawing-box { text-align: center; }
        .drawing-box img { width: 78mm; height: auto; }
        .armario-wrap { text-align: right; margin-bottom: 6px; }
        h2.mecanizados {
            text-align: center;
            color: #b91c1c;
            font-size: 16px;
            margin: 8px 0 12px;
            letter-spacing: 1px;
        }
        .machining { text-align: center; }
        .machining img { width: 175mm; height: auto; }
        table.machining-extra { width: 140mm; margin: 8px auto 0; border-collapse: collapse; }
        table.machining-extra td { font-size: 10px; padding: 2px 8px; text-align: left; }
        table.machining-extra td.nm { font-weight: bold; width: 42%; }
        .page-break { page-break-before: always; }
        .date { text-align: right; font-size: 9px; margin: 0 0 2px; }
    </style>
</head>
<body>
    <div class="date">{{ $generatedAt }}</div>
    <table class="layout">
        <tr>
            <td class="parts">
                <h1>{{ $title }}</h1>
                <div class="module-line">
                    MODULO
                    <span>{{ $module['alto_mm'] }} &nbsp; X &nbsp; {{ $module['ancho_mm'] }} &nbsp; X &nbsp; {{ $module['fondo_mm'] }}</span>
                </div>

                <div class="caption">MEDIDAS HUECO FRENTE</div>
                <table class="hueco">
                    <tr>
                        <th>LARGO</th>
                        <th>ANCHO</th>
                        <th>ZOCALO</th>
                        <th>UNIDADES</th>
                    </tr>
                    <tr>
                        <td>{{ $hueco['largo_mm'] }}</td>
                        <td>{{ $hueco['ancho_mm'] }}</td>
                        <td class="zocalo">{{ $hueco['zocalo_mm'] }}</td>
                        <td class="units">{{ $hueco['unidades'] }}</td>
                    </tr>
                </table>

                <div class="material">
                    <span class="tag">MATERIAL</span>
                    <span class="name">{{ $material }}</span>
                </div>

                <table class="list">
                    @foreach($parts as $row)
                        <tr>
                            <td class="nm">{{ $row['name'] }}</td>
                            <td class="qt">{{ $row['qty'] }}</td>
                            <td class="ms">{{ $row['measures'] }}</td>
                        </tr>
                    @endforeach
                    @foreach($hardware as $row)
                        <tr>
                            <td class="nm">{{ $row['name'] }}</td>
                            <td class="qt">{{ $row['qty'] }}</td>
                            <td class="ms">{{ $row['measures'] }}</td>
                        </tr>
                    @endforeach
                </table>

                @if(count($supplements))
                    <h2 class="block">SUPLEMENTOS PARA BASE</h2>
                    <table class="list">
                        @foreach($supplements as $row)
                            <tr>
                                <td class="nm">{{ $row['name'] }}</td>
                                <td class="qt">{{ $row['qty'] }}</td>
                                <td class="ms">{{ $row['measures'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                @if(!empty($drawers))
                    @if($drawers['frentes'])
                        <div class="frentes">FRENTES SIN TIRADOR</div>
                    @endif
                    <div class="cajonera">
                        <span class="num">1</span>
                        <span class="name">CAJONERA</span>
                        <span class="num">{{ $drawers['title_count'] }}</span>
                        <span class="name">CAJONES{{ $drawers['width_mm'] > 0 ? ' DE '.$drawers['width_mm'] : '' }}</span>
                    </div>
                    @foreach($drawers['lines'] as $line)
                        <div class="drawer-line">{{ $line }}</div>
                    @endforeach
                @endif

                <div class="muted">Medidas en milímetros. Referencia: {{ $reference }}</div>
            </td>
            <td class="drawing">
                <div class="armario-wrap">
                    <div class="caption">MEDIDAS DE ARMARIO</div>
                    <table class="armario" align="right">
                        <tr>
                            <th>ALTO</th>
                            <th>ANCHO</th>
                            <th>FONDO</th>
                        </tr>
                        <tr>
                            <td>{{ $module['alto_mm'] }}</td>
                            <td>{{ $module['ancho_mm'] }}</td>
                            <td>{{ $module['fondo_mm'] }}</td>
                        </tr>
                    </table>
                </div>
                @if($elevation !== '')
                    <div class="drawing-box">
                        <img src="{{ $elevation }}" alt="Alzado del mueble">
                    </div>
                @endif
            </td>
        </tr>
    </table>

    @if($showMachiningPage)
        <div class="page-break">
            <div class="date">{{ $generatedAt }}</div>
            <h1 style="text-align:left;">{{ $title }}</h1>
            <h2 class="mecanizados">MECANIZADOS</h2>
            @if($machiningImage !== '')
                <div class="machining">
                    <img src="{{ $machiningImage }}" alt="Mecanizados">
                </div>
            @endif
            @if(count($machiningExtras ?? []))
                <table class="machining-extra">
                    @foreach($machiningExtras as $row)
                        <tr>
                            <td class="nm">{{ $row['name'] }}</td>
                            <td>{{ $row['position'] }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @endif
</body>
</html>
