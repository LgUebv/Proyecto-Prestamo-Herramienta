<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Prestamo;
use App\Models\Almacenista;
use Livewire\WithPagination;
use App\Models\Herramienta;
use App\Models\PrestamoDetalles;
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

    // Modal de devolución
    public $modalDevolucionAbierto = false;
    public $prestamo_id_devolver = null;
    public $almacenista_recibe_id = '';

    // Estado de cada herramienta al recibirla / al corregir: [detalle_id => ['danada' => bool, 'nota' => '']]
    public $estadoHerramientas = [];
    public $herramientasModal = [];

    // Modal para corregir una devolución ya registrada
    public $modalCorreccionAbierto = false;
    public $prestamo_id_corregir = null;
    public $reabrirPrestamo = false;

    private const PREFIJO_DANADA = 'Dañada: ';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFechaInicio() { $this->resetPage(); }
    public function updatingFechaFin() { $this->resetPage(); }

    public function limpiarFiltros()
    {
        $this->reset(['search', 'fecha_inicio', 'fecha_fin']);
        $this->resetPage();
    }

    // Carga las herramientas del préstamo para el modal; las que ya están en mantenimiento
    // (por una corrección anterior) aparecen marcadas como dañadas con su nota.
    private function cargarHerramientas(Prestamo $prestamo): void
    {
        $this->herramientasModal = [];
        $this->estadoHerramientas = [];

        foreach ($prestamo->detalles as $detalle) {
            $nota = str_starts_with((string) $detalle->condicion_devolucion, self::PREFIJO_DANADA)
                ? substr($detalle->condicion_devolucion, strlen(self::PREFIJO_DANADA))
                : '';

            $this->herramientasModal[] = [
                'detalle_id' => $detalle->id,
                'nombre' => $detalle->herramienta->nombre ?? 'Herramienta eliminada',
                'codigo' => $detalle->herramienta->codigo_barras ?? 'N/A',
            ];
            $this->estadoHerramientas[$detalle->id] = [
                // '1' / '0' como texto para que los botones de opción de la vista queden marcados
                'danada' => ($detalle->herramienta->disponibilidad ?? null) === 'mantenimiento' ? '1' : '0',
                'nota' => $nota,
            ];
        }
    }

    // Las herramientas marcadas como dañadas deben llevar una descripción del daño
    private function validarDanos(): void
    {
        $reglas = [];
        foreach ($this->estadoHerramientas as $detalleId => $estado) {
            if (!empty($estado['danada'])) {
                $reglas["estadoHerramientas.$detalleId.nota"] = 'required|string|max:500';
            }
        }

        if ($reglas) {
            $this->validate($reglas, ['estadoHerramientas.*.nota.required' => 'Describe el daño de esta herramienta.']);
        }
    }

    private function aplicarEstado(PrestamoDetalles $detalle, bool $prestada): void
    {
        $estado = $this->estadoHerramientas[$detalle->id] ?? ['danada' => '0', 'nota' => ''];
        $danada = !empty($estado['danada']);

        $detalle->update([
            'condicion_devolucion' => $danada ? self::PREFIJO_DANADA . trim($estado['nota']) : 'Buen estado',
        ]);

        // Dañada → Mantenimiento (aunque el préstamo siga abierto). Buena → disponible, o prestada si se reabre.
        Herramienta::where('id', $detalle->herramienta_id)->update([
            'disponibilidad' => $danada ? 'mantenimiento' : ($prestada ? 'prestada' : 'disponible'),
        ]);
    }

    public function abrirModalDevolucion($id)
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
        $this->resetErrorBag();

        $this->prestamo_id_devolver = $id;
        // Por defecto recibe el almacenista vinculado a la sesión actual (si está activo)
        $this->almacenista_recibe_id = (string) (Almacenista::where('user_id', auth()->id())
            ->where('estado', 'activo')
            ->value('id') ?? '');
        $this->cargarHerramientas(Prestamo::with('detalles.herramienta')->findOrFail($id));
        $this->modalDevolucionAbierto = true;
    }

    public function cerrarModalDevolucion()
    {
        $this->modalDevolucionAbierto = false;
        $this->prestamo_id_devolver = null;
        $this->almacenista_recibe_id = '';
        $this->herramientasModal = [];
        $this->estadoHerramientas = [];
    }

    public function confirmarDevolucion()
    {
        // Obligamos a que seleccionen un almacenista
        $this->validate([
            'almacenista_recibe_id' => 'required'
        ], [
            'almacenista_recibe_id.required' => 'Debes indicar qué almacenista está recibiendo el equipo.'
        ]);
        $this->validarDanos();

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
            $danadas = 0;

            DB::transaction(function () use ($recibe, &$danadas) {
                $prestamo = Prestamo::with('detalles')->lockForUpdate()->findOrFail($this->prestamo_id_devolver);

                // Un préstamo ya devuelto no se vuelve a procesar (liberaría herramientas prestadas a otra persona)
                if ($prestamo->estado_prestamo === 'devuelto') {
                    throw new \DomainException('Este préstamo ya fue devuelto.');
                }

                $prestamo->update([
                    'fecha_devolucion_real' => now(),
                    'estado_prestamo' => 'devuelto',
                ]);

                foreach ($prestamo->detalles as $detalle) {
                    // Firma del almacenista que recibe
                    $detalle->update(['recibido_por_id' => $recibe->user_id]);
                    $this->aplicarEstado($detalle, prestada: false);
                    $danadas += !empty($this->estadoHerramientas[$detalle->id]['danada']) ? 1 : 0;
                }
            });

            $this->mensajeExito = '¡Herramientas devueltas y firmadas exitosamente!'
                . ($danadas ? " {$danadas} herramienta(s) se enviaron a Mantenimiento." : '');
            $this->cerrarModalDevolucion();

        } catch (\DomainException $e) {
            $this->mensajeError = $e->getMessage();
            $this->cerrarModalDevolucion();
        } catch (\Exception $e) {
            $this->mensajeError = 'Ocurrió un error al registrar la devolución: ' . $e->getMessage();
        }
    }

    // --- Corregir una devolución ya registrada (p. ej. la herramienta llegó descompuesta y no se revisó) ---

    public function abrirModalCorreccion($id)
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
        $this->resetErrorBag();

        $prestamo = Prestamo::with('detalles.herramienta')->findOrFail($id);

        if ($prestamo->estado_prestamo !== 'devuelto') {
            $this->mensajeError = 'Este préstamo no está marcado como devuelto.';
            return;
        }

        $this->prestamo_id_corregir = $id;
        $this->reabrirPrestamo = false;
        $this->cargarHerramientas($prestamo);
        $this->modalCorreccionAbierto = true;
    }

    public function cerrarModalCorreccion()
    {
        $this->modalCorreccionAbierto = false;
        $this->prestamo_id_corregir = null;
        $this->reabrirPrestamo = false;
        $this->herramientasModal = [];
        $this->estadoHerramientas = [];
    }

    public function guardarCorreccion()
    {
        $this->validarDanos();

        try {
            DB::transaction(function () {
                $prestamo = Prestamo::with('detalles.herramienta')->lockForUpdate()->findOrFail($this->prestamo_id_corregir);

                if ($prestamo->estado_prestamo !== 'devuelto') {
                    throw new \DomainException('Este préstamo no está marcado como devuelto.');
                }

                // Si alguna herramienta ya salió en otro préstamo activo, no se puede corregir
                $prestadasEnOtro = PrestamoDetalles::whereIn('herramienta_id', $prestamo->detalles->pluck('herramienta_id'))
                    ->where('prestamo_id', '!=', $prestamo->id)
                    ->whereHas('prestamo', fn ($q) => $q->where('estado_prestamo', 'activo'))
                    ->with('herramienta')
                    ->get()
                    ->pluck('herramienta.codigo_barras')
                    ->unique();

                if ($prestadasEnOtro->isNotEmpty()) {
                    throw new \DomainException('No se puede corregir: estas herramientas ya están en otro préstamo activo: ' . $prestadasEnOtro->implode(', ') . '.');
                }

                if ($this->reabrirPrestamo) {
                    // El préstamo vuelve a estar activo: el prestatario sigue siendo responsable
                    $prestamo->update(['estado_prestamo' => 'activo', 'fecha_devolucion_real' => null]);
                }

                foreach ($prestamo->detalles as $detalle) {
                    if ($this->reabrirPrestamo) {
                        $detalle->update(['recibido_por_id' => null]);
                    }
                    $this->aplicarEstado($detalle, prestada: $this->reabrirPrestamo);
                }
            });

            $this->mensajeExito = $this->reabrirPrestamo
                ? 'Devolución corregida. El préstamo se reabrió y el prestatario sigue siendo responsable.'
                : 'Devolución corregida. Se actualizó el estado de las herramientas.';
            $this->cerrarModalCorreccion();

        } catch (\DomainException $e) {
            $this->mensajeError = $e->getMessage();
            $this->cerrarModalCorreccion();
        } catch (\Exception $e) {
            $this->mensajeError = 'Ocurrió un error al corregir la devolución: ' . $e->getMessage();
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