<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Prestatario;
use App\Models\Herramienta;
use App\Models\Prestamo;
use App\Models\PrestamoDetalles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
class NuevoPrestamo extends Component
{
    // Variables de inputs
    public $numeroControl = '';
    public $codigoBarras = '';
    public $materia = '';
    public $aceptoTerminos = false;
    
    // Variables para usuarios nuevos
    public $nombreCompleto = '';
    public $esNuevoRegistro = false;
    
    // Variables de estado general
    public $prestatario = null;
    public $carrito = []; 
    public $mensajeError = '';
    public $mensajeExito = '';

    public $es_trabajador = false; 
    public $nombre_trabajador = '';

    // --- VARIABLES DEL CARRITO/PRESTAMO ---
    public $codigoEscaneado = ''; 

    public function updatedEsTrabajador()
    {
        $this->numeroControl = '';
        $this->nombre_trabajador = '';
        $this->resetValidation();
    }

    public function buscarPrestatario()
    {
        $this->mensajeError = ''; $this->mensajeExito = '';
        $this->numeroControl = trim($this->numeroControl); 

        if (strlen($this->numeroControl) !== 9 && !str_starts_with($this->numeroControl, 'T-')) {
            $this->mensajeError = 'El número de control debe tener exactamente 9 dígitos (o empezar con T- para trabajadores).';
            $this->esNuevoRegistro = false;
            $this->prestatario = null;
            return;
        }

        $usuario = Prestatario::where('numero_control', $this->numeroControl)->first();

        if ($usuario) {
            if ($usuario->estado !== 'activo') {
                $this->mensajeError = "El usuario {$usuario->nombre_completo} está inactivo o suspendido.";
                $this->prestatario = null;
                return;
            }
            $this->prestatario = $usuario;
            $this->nombreCompleto = $usuario->nombre_completo;
            $this->esNuevoRegistro = false;
        } else {
            $this->prestatario = null;
            $this->nombreCompleto = '';
            $this->esNuevoRegistro = true;
        }
    }

    public function limpiarPrestatario()
    {
        $this->reset(['prestatario', 'numeroControl', 'nombreCompleto', 'esNuevoRegistro', 'mensajeError', 'es_trabajador', 'nombre_trabajador']);
    }

    public function agregarAlCarrito()
    {
        $this->mensajeError = ''; $this->mensajeExito = '';

        $herramienta = Herramienta::with('categoria')->where('codigo_barras', $this->codigoBarras)->first();

        if (!$herramienta || $herramienta->disponibilidad !== 'disponible') {
            $this->mensajeError = 'Herramienta no encontrada o no está disponible.';
            $this->codigoBarras = '';
            return;
        }

        if (collect($this->carrito)->contains('id', $herramienta->id)) {
            $this->mensajeError = 'Esta herramienta ya está en la lista.';
            $this->codigoBarras = '';
            return;
        }

        $this->carrito[] = [
            'id' => $herramienta->id,
            'codigo_barras' => $herramienta->codigo_barras,
            'nombre' => $herramienta->nombre,
            'marca' => $herramienta->marca,
            'categoria' => $herramienta->categoria ? $herramienta->categoria->nombre : 'Sin Categoría',
            'estado_fisico' => $herramienta->estado_fisico,
        ];

        $this->codigoBarras = ''; 
    }

    public function quitarDelCarrito($indice)
    {
        unset($this->carrito[$indice]);
        $this->carrito = array_values($this->carrito); 
    }

    // El préstamo vence hoy a las 8:00 p.m.; si se registra a esa hora o después, vence mañana a las 8:00 p.m.
    public function calcularFechaLimite()
    {
        $limite = now()->setTime(20, 0);

        return now()->gte($limite) ? $limite->addDay() : $limite;
    }

    public function confirmarPrestamo()
    {
        $this->mensajeError = ''; 
        $this->mensajeExito = '';

        // 1. Validación de campos generales
        if (empty($this->carrito) || empty($this->materia) || !$this->aceptoTerminos) {
            $this->mensajeError = 'Faltan datos: Asegúrate de llenar la materia, agregar herramientas y aceptar los términos.';
            return;
        }


        // 2. Validación específica según el tipo de persona
        if ($this->es_trabajador) {
            if (empty($this->nombre_trabajador)) {
                $this->mensajeError = 'Debes ingresar el nombre del trabajador externo.';
                return;
            }
        } else {
            if ($this->esNuevoRegistro && empty($this->nombreCompleto)) {
                $this->mensajeError = 'Debes ingresar el nombre para registrar a este nuevo usuario.';
                return;
            }

            if (!$this->esNuevoRegistro && !$this->prestatario) {
                $this->mensajeError = 'Debes buscar un usuario válido antes de continuar.';
                return;
            }
        }

        try {
            DB::transaction(function () {
                // 3. Crear el Prestatario si es necesario
                if ($this->es_trabajador) {
                    $this->prestatario = Prestatario::firstOrCreate(
                        ['nombre_completo' => $this->nombre_trabajador],
                        [
                            'numero_control' => 'EXT-' . time(), 
                            'tipo_usuario' => 'Trabajador Externo',
                            'estado' => 'activo' 
                        ]
                    );
                } elseif ($this->esNuevoRegistro) {
                    $this->prestatario = Prestatario::create([
                        'numero_control' => $this->numeroControl,
                        'nombre_completo' => $this->nombreCompleto,
                        'tipo_usuario' => str_starts_with($this->numeroControl, 'T-') ? 'Trabajador' : 'Alumno',
                        'estado' => 'activo' 
                    ]);
                }

                // 4. Crear el Préstamo principal
                $prestamo = Prestamo::create([
                    'prestatario_id' => $this->prestatario->id,
                    'user_id' => Auth::id(),
                    'materia' => $this->materia,
                    'fecha_prestamo' => now(),
                    'fecha_limite' => $this->calcularFechaLimite(),
                    'estado_prestamo' => 'activo',
                    'acepto_terminos' => $this->aceptoTerminos,
                ]);

                // 5. Registrar detalles y firmas de auditoría
                foreach ($this->carrito as $item) {
                    PrestamoDetalles::create([
                        'prestamo_id' => $prestamo->id,
                        'herramienta_id' => $item['id'],
                        'cantidad' => 1, 
                        'condicion_entrega' => $item['estado_fisico'],
                        
                        // Firma de auditoría: Quién lo prestó
                        'entregado_por_id' => Auth::id(),
                    ]);
                    Herramienta::where('id', $item['id'])->update(['disponibilidad' => 'prestada']);
                }
            });

            // 6. Limpieza final
            $this->reset([
                'numeroControl', 'codigoBarras', 'prestatario', 'carrito', 
                'materia', 'aceptoTerminos', 'nombreCompleto', 
                'esNuevoRegistro', 'es_trabajador', 'nombre_trabajador'
            ]);
            
            $this->mensajeExito = '¡Préstamo guardado y firmado correctamente!';

        } catch (\Exception $e) {
            $this->mensajeError = 'Error al guardar en base de datos: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.nuevo-prestamo');
    }
}