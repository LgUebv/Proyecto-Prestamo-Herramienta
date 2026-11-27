<?php

namespace App\Exports;

use App\Models\Herramienta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventarioExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function collection()
    {
        return Herramienta::resumenInventario()->get();
    }

    public function map($item): array
    {
        return [
            $item->nombre,
            $item->marca,
            (int) $item->total_piezas,
            (int) $item->disponibles,
            (int) $item->prestadas,
            (int) $item->en_mantenimiento,
        ];
    }

    public function headings(): array
    {
        return ['Herramienta', 'Marca', 'Stock Total', 'Disponibles', 'Prestadas', 'En Mantenimiento'];
    }
}
