<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Prestamo;
use App\Models\Almacenista;
use Livewire\WithPagination;
use App\Models\Herramienta;
use Illuminate\Support\Facades\DB;
use App\Exports\PrestamosExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

#[Layout('layouts.app')]
class AdminPrestamos extends Component
{
    use WithPagination;

    public $search = '';
    public $mensajeExito = '';
    public $mensajeError = '';

    public $fecha_inicio;
    public $fecha_fin;

    // NUEVO: Variables para el Modal de Devolución
    public $modalDevolucionAbierto = false;
    public $prestamo_id_devolver = null;
    public $almacenista_recibe_id = '';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFechaInicio() { $this->resetPage(); }
    public function updatingFechaFin() { $this->resetPage(); }

    public function limpiarFiltros()
    {
        $this->reset(['search', 'fecha_inicio', 'fecha_fin']);
        $this->resetPage();
    }

    // NUEVO: Funciones para manejar la ventana de devolución
    public function abrirModalDevolucion($id)
    {
        $this->prestamo_id_devolver = $id;
        $this->almacenista_recibe_id = '';
        $this->modalDevolucionAbierto = true;
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function cerrarModalDevolucion()
    {
        $this->modalDevolucionAbierto = false;
        $this->prestamo_id_devolver = null;
        $this->almacenista_recibe_id = '';
    }

    public function confirmarDevolucion()
    {
        // Obligamos a que seleccionen un almacenista
        $this->validate([
            'almacenista_recibe_id' => 'required'
        ], [
            'almacenista_recibe_id.required' => 'Debes indicar qué almacenista está recibiendo el equipo.'
        ]);

        // Solo puede recibir un almacenista activo con cuenta vinculada
        $recibe = Almacenista::where('id', $this->almacenista_recibe_id)
            ->where('estado', 'activo')
            ->whereNotNull('user_id')
            ->first();

        if (!$recibe) {
            $this->addError('almacenista_recibe_id', 'Selecciona un almacenista activo.');
            return;
        }

        try {
            DB::transaction(function () use ($recibe) {
                $prestamo = Prestamo::with('detalles')->findOrFail($this->prestamo_id_devolver);

                $prestamo->update([
                    'fecha_devolucion_real' => now(),
                    'estado_prestamo' => 'devuelto',
                ]);

                foreach ($prestamo->detalles as $detalle) {
                    // Aquí es donde grabamos la firma del que recibe
                    $detalle->update([
                        'recibido_por_id' => $recibe->user_id
                    ]);

                    Herramienta::where('id', $detalle->herramienta_id)
                        ->update(['disponibilidad' => 'disponible']);
                }
            });

            $this->mensajeExito = '¡Herramientas devueltas y firmadas exitosamente!';
            $this->cerrarModalDevolucion();

        } catch (\Exception $e) {
            $this->mensajeError = 'Ocurrió un error al registrar la devolución: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $prestamos = Prestamo::with([
                'prestatario', 
                'detalles.herramienta', 
                'detalles.entregadoPor', 
                'detalles.recibidoPor'
            ])
            ->whereHas('prestatario', function($query) {
                $query->where('nombre_completo', 'like', '%'.$this->search.'%')
                      ->orWhere('numero_control', 'like', '%'.$this->search.'%');
            })
            ->when($this->fecha_inicio, function ($query) {
                $query->whereDate('fecha_prestamo', '>=', $this->fecha_inicio);
            })
            ->when($this->fecha_fin, function ($query) {
                $query->whereDate('fecha_prestamo', '<=', $this->fecha_fin);
            })
            ->orderBy('fecha_prestamo', 'desc')
            ->paginate(6); 

        // Almacenistas activos para el selector de devoluciones
        $almacenistas = Almacenista::where('estado', 'activo')
            ->whereNotNull('user_id')
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo']);

        return view('livewire.admin-prestamos', compact('prestamos', 'almacenistas'));
    }

    public function exportarExcel() {
        $nombreArchivo = 'reporte_prestamos_' . now()->format('d-m-Y_H-i') . '.xlsx';
        return Excel::download(new PrestamosExport($this->fecha_inicio, $this->fecha_fin), $nombreArchivo);
    }

    public function exportarPdf() {
        $prestamos = Prestamo::with(['prestatario', 'detalles.herramienta', 'detalles.entregadoPor', 'detalles.recibidoPor'])
            ->when($this->fecha_inicio, fn($q) => $q->whereDate('fecha_prestamo', '>=', $this->fecha_inicio))
            ->when($this->fecha_fin, fn($q) => $q->whereDate('fecha_prestamo', '<=', $this->fecha_fin))
            ->orderBy('fecha_prestamo', 'desc')
            ->get();

        $pdf = Pdf::loadView('reportes.prestamos_pdf', ['data' => $prestamos]);
        $nombreArchivo = 'reporte_prestamos_' . now()->format('d-m-Y_H-i') . '.pdf';
        return response()->streamDownload(fn() => print($pdf->output()), $nombreArchivo);
    }
}