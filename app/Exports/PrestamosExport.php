<?php

namespace App\Exports;

use App\Models\Prestamo;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PrestamosExport implements FromQuery, WithHeadings, WithMapping
{
    protected $fecha_inicio, $fecha_fin;

    public function __construct($inicio, $fin) {
        $this->fecha_inicio = $inicio;
        $this->fecha_fin = $fin;
    }

    public function query() {
        return Prestamo::query()->with([
            'prestatario', 
            'detalles.herramienta', 
            'detalles.entregadoPor', 
            'detalles.recibidoPor'
        ])
        ->when($this->fecha_inicio, fn($q) => $q->whereDate('fecha_prestamo', '>=', $this->fecha_inicio))
        ->when($this->fecha_fin, fn($q) => $q->whereDate('fecha_prestamo', '<=', $this->fecha_fin));
    }

    public function headings(): array
    {
        return [
            'Folio',
            'Prestatario',
            'Herramientas y Marcas',
            'Materia / Proyecto',
            'Fecha Salida',
            'Fecha Devolución',
            'Almacenista que Entregó',
            'Almacenista que Recibió',
            'Herramientas Dañadas',
            'Estado'
        ];
    }

    public function map($prestamo): array
    {
        // 1. Unimos todas las herramientas en un solo texto: "1x Taladro (DeWalt), 2x Pinzas (Truper)"
        $herramientasTexto = $prestamo->detalles->map(function($detalle) {
            $nombre = $detalle->herramienta->nombre ?? 'Desconocida';
            $marca = !empty($detalle->herramienta->marca) ? ' (' . $detalle->herramienta->marca . ')' : '';
            return $detalle->cantidad . 'x ' . $nombre . $marca;
        })->implode(', ');

        // Herramientas que regresaron dañadas, con la descripción del daño
        $danadas = $prestamo->detalles->filter->estaDanada()->map(function ($detalle) {
            $nombre = $detalle->herramienta->nombre ?? 'Desconocida';
            $codigo = $detalle->herramienta->codigo_barras ?? 'N/A';
            return "{$nombre} ({$codigo}): " . $detalle->notaDano();
        })->implode('; ');

        // 2. Obtenemos las firmas
        $primerDetalle = $prestamo->detalles->first();
        $firmaSalida = $primerDetalle && $primerDetalle->entregadoPor ? $primerDetalle->entregadoPor->name : 'N/A';
        $firmaDevolucion = $primerDetalle && $primerDetalle->recibidoPor ? $primerDetalle->recibidoPor->name : 'Pendiente';

        // 3. Imprimimos la fila de Excel
        return [
            $prestamo->id,
            $prestamo->prestatario->nombre_completo ?? 'N/A',
            $herramientasTexto, // <-- Aquí va el texto formateado con las marcas
            $prestamo->materia,
            \Carbon\Carbon::parse($prestamo->fecha_prestamo)->format('d/m/Y H:i'),
            $prestamo->fecha_devolucion_real ? \Carbon\Carbon::parse($prestamo->fecha_devolucion_real)->format('d/m/Y H:i') : 'Pendiente',
            $firmaSalida,
            $firmaDevolucion,
            $danadas ?: 'Ninguna',
            ucfirst($prestamo->estado_prestamo)
        ];
    }
}