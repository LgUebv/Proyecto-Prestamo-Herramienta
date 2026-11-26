<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamos</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .header h2 { margin: 0; color: #1f2937; }
        .header p { margin: 5px 0 0; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f3f4f6; font-weight: bold; color: #374151; }
        .status-activo { color: #dc2626; font-weight: bold; } /* Rojo */
        .status-devuelto { color: #16a34a; font-weight: bold; } /* Verde */
        ul { margin: 0; padding-left: 15px; }
        .danada { color: #c2410c; font-weight: bold; }
        .nota-dano { display: block; color: #c2410c; font-size: 9px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Historial y Reporte de Préstamos</h2>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID / Prestatario</th>
                <th>Herramienta (Marca)</th>
                <th>Materia</th>
                <th>Tiempos (Salida / Dev.)</th>
                <th>Firmas Almacén</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $prestamo)
            @php
                // Extraemos el primer detalle para leer a los almacenistas responsables
                $primerDetalle = $prestamo->detalles->first();
            @endphp
            <tr>
                <td>
                    <strong>{{ $prestamo->prestatario->nombre_completo ?? 'N/A' }}</strong><br>
                    <span style="font-size: 9px; color: #666;">{{ $prestamo->prestatario->numero_control ?? '' }}</span>
                </td>
                
                <td>
                    <ul style="margin: 0; padding-left: 15px; font-size: 10px;">
                        @foreach($prestamo->detalles as $detalle)
                            <li>
                                <strong>{{ $detalle->cantidad }}x {{ $detalle->herramienta->nombre ?? 'Sin nombre' }}</strong> 
                                @if(!empty($detalle->herramienta->marca))
                                    <span style="color: #666;">({{ $detalle->herramienta->marca }})</span>
                                @endif
                                @if($detalle->estaDanada())
                                    <span class="danada">[DAÑADA]</span>
                                    <span class="nota-dano">{{ $detalle->notaDano() }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </td>
                
                <td>{{ $prestamo->materia }}</td>
                
                <td style="font-size: 10px;">
                    <strong>Salió:</strong> {{ \Carbon\Carbon::parse($prestamo->fecha_prestamo)->format('d/m/y H:i') }}<br>
                    <strong>Regresó:</strong> 
                    @if($prestamo->fecha_devolucion_real)
                        {{ \Carbon\Carbon::parse($prestamo->fecha_devolucion_real)->format('d/m/y H:i') }}
                    @else
                        <em>Pendiente</em>
                    @endif
                </td>

                <td style="font-size: 10px;">
                    <strong>Entregó:</strong> {{ $primerDetalle && $primerDetalle->entregadoPor ? $primerDetalle->entregadoPor->name : 'N/A' }}<br>
                    <strong>Recibió:</strong> 
                    @if($primerDetalle && $primerDetalle->recibidoPor)
                        {{ $primerDetalle->recibidoPor->name }}
                    @else
                        <em>Pendiente</em>
                    @endif
                </td>

                <td>
                    @if($prestamo->estado_prestamo == 'devuelto')
                        <span class="status-devuelto">DEVUELTO</span>
                    @else
                        <span class="status-activo">ACTIVO</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No se encontraron registros en este reporte.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>