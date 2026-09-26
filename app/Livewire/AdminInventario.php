<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Herramienta;
use App\Exports\InventarioExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

#[Layout('layouts.app')]
class AdminInventario extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch() { $this->resetPage(); }

    public function exportarExcel() {
    $nombreArchivo = 'inventario_' . now()->format('d-m-Y_H-i') . '.xlsx';
    return Excel::download(new InventarioExport, $nombreArchivo);
}

public function exportarPdf() {
        $data = Herramienta::selectRaw('nombre, marca, COUNT(*) as total_piezas, 
                SUM(CASE WHEN disponibilidad = "disponible" THEN 1 ELSE 0 END) as disponibles')
                ->groupBy('nombre', 'marca')
                ->get();
                
        $pdf = Pdf::loadView('reportes.inventario_pdf', compact('data'));
        
        $nombreArchivo = 'inventario_' . now()->format('d-m-Y_H-i') . '.pdf';
        return response()->streamDownload(fn() => print($pdf->output()), $nombreArchivo);
    }

    public function render()
    {
        // Consulta agrupada y calculada en tiempo real
        $inventario = Herramienta::selectRaw('
                nombre, 
                marca, 
                COUNT(*) as total_piezas, 
                SUM(CASE WHEN disponibilidad = "disponible" THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN disponibilidad = "prestada" THEN 1 ELSE 0 END) as prestadas,
                SUM(CASE WHEN disponibilidad = "mantenimiento" THEN 1 ELSE 0 END) as en_mantenimiento
            ')
            ->when($this->search, function($query) {
                $query->where('nombre', 'like', '%'.$this->search.'%')
                      ->orWhere('marca', 'like', '%'.$this->search.'%');
            })
            ->groupBy('nombre', 'marca')
            ->orderBy('nombre')
            ->orderBy('marca')
            ->paginate(10);

        return view('livewire.admin-inventario', compact('inventario'));
    }
}