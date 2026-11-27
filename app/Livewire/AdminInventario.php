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
        $data = Herramienta::resumenInventario()->get();
                
        $pdf = Pdf::loadView('reportes.inventario_pdf', compact('data'));
        
        $nombreArchivo = 'inventario_' . now()->format('d-m-Y_H-i') . '.pdf';
        return response()->streamDownload(fn() => print($pdf->output()), $nombreArchivo);
    }

    public function render()
    {
        // Consulta agrupada y calculada en tiempo real
        $inventario = Herramienta::resumenInventario()
            ->when($this->search, function($query) {
                $query->where('nombre', 'like', '%'.$this->search.'%')
                      ->orWhere('marca', 'like', '%'.$this->search.'%');
            })
            ->paginate(10);

        return view('livewire.admin-inventario', compact('inventario'));
    }
}