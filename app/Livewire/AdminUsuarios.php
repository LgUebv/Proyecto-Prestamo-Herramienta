<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Prestatario;

#[Layout('layouts.app')]
class AdminUsuarios extends Component
{
    use WithPagination;

    public $search = '';
    public $modalAbierto = false;
    
    // Variables del formulario
    public $id_usuario;
    public $numero_control, $nombre_completo, $tipo_usuario, $carrera_departamento;
    public $semestre, $grupo, $telefono, $estado;

    public function updatingSearch() { $this->resetPage(); }

    public function abrirModal() 
    { 
        $this->resetInput(); 
        $this->modalAbierto = true; 
    }

    public function cerrarModal() 
    { 
        $this->modalAbierto = false; 
    }

    public function resetInput() 
    {
        $this->id_usuario = null;
        $this->numero_control = '';
        $this->nombre_completo = '';
        $this->tipo_usuario = 'Alumno'; // Valor por defecto
        $this->carrera_departamento = '';
        $this->semestre = '';
        $this->grupo = '';
        $this->telefono = '';
        $this->estado = 'activo';
    }

    public function guardar()
    {
        Prestatario::updateOrCreate(['id' => $this->id_usuario], [
            'numero_control' => $this->numero_control,
            'nombre_completo' => $this->nombre_completo,
            'tipo_usuario' => $this->tipo_usuario,
            'carrera_departamento' => $this->carrera_departamento,
            'semestre' => $this->semestre,
            'grupo' => $this->grupo,
            'telefono' => $this->telefono,
            'estado' => $this->estado,
        ]);
        
        $this->cerrarModal();
    }

    public function editar($id)
    {
        $usuario = Prestatario::findOrFail($id);
        $this->id_usuario = $id;
        $this->numero_control = $usuario->numero_control;
        $this->nombre_completo = $usuario->nombre_completo;
        $this->tipo_usuario = $usuario->tipo_usuario;
        $this->carrera_departamento = $usuario->carrera_departamento;
        $this->semestre = $usuario->semestre;
        $this->grupo = $usuario->grupo;
        $this->telefono = $usuario->telefono;
        $this->estado = $usuario->estado;
        
        $this->modalAbierto = true;
    }

    public function borrar($id) 
    { 
        $this->mensajeExito = '';
        $this->mensajeError = '';

        try {
            $usuario = \App\Models\Prestatario::findOrFail($id);

            // Verificamos si el usuario tiene préstamos registrados
            // (Asegúrate de tener la relación public function prestamos() en tu modelo Prestatario)
            $tienePrestamos = \App\Models\Prestamo::where('prestatario_id', $id)->exists();

            if ($tienePrestamos) {
                // Si tiene historial, NO lo borramos. Solo lo desactivamos.
                $usuario->update(['estado' => 'inactivo']);
                $this->mensajeExito = 'El usuario tiene préstamos en su historial. Se ha marcado como INACTIVO por seguridad.';
            } else {
                // Si es un usuario nuevo que nunca pidió nada, sí podemos borrarlo por completo.
                $usuario->delete();
                $this->mensajeExito = 'Usuario eliminado de la base de datos correctamente.';
            }

        } catch (\Exception $e) {
            $this->mensajeError = 'Error al procesar la solicitud: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $usuarios = Prestatario::where('nombre_completo', 'like', '%' . $this->search . '%')
            ->orWhere('numero_control', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);
            
        return view('livewire.admin-usuarios', compact('usuarios'));
    }
}