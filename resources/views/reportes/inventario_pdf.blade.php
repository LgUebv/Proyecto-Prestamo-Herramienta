<style>
    body { font-family: sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .header { text-align: center; margin-bottom: 30px; }
</style>

<div class="header">
    <h2>Reporte de Inventario de Almacén</h2>
    <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>Herramienta</th>
            <th>Marca</th>
            <th>Stock Total</th>
            <th>Disponibles</th>
            <th>Prestadas</th>
            <th>En Mantenimiento</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $item)
        <tr>
            <td>{{ $item->nombre }}</td>
            <td>{{ $item->marca }}</td>
            <td>{{ $item->total_piezas }}</td>
            <td>{{ $item->disponibles }}</td>
            <td>{{ $item->prestadas }}</td>
            <td>{{ $item->en_mantenimiento }}</td>
        </tr>
        @endforeach
    </tbody>
</table>