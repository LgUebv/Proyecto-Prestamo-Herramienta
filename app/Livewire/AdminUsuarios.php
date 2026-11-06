<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Prestatario;

#[Layout('layouts.app')]
class AdminUsuarios extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $modalAbierto = false;
    
    // Variables del formulario
    public $id_usuario;
    public $numero_control, $nombre_completo, $tipo_usuario, $carrera_departamento;
    public $semestre, $grupo, $telefono, $estado;
    public $foto;              // Foto nueva tomada con la cámara
    public $fotoActual = null; // URL de la foto guardada (al editar)

    public $mensajeExito = '';
    public $mensajeError = '';

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
        $this->foto = null;
        $this->fotoActual = null;
        $this->resetValidation();
    }

    public function guardar()
    {
        // La foto es obligatoria al registrar; al editar solo se cambia si se toma una nueva
        $this->validate(
            [
                'foto' => ($this->id_usuario ? 'nullable' : 'required') . '|image|max:5120',
                'semestre' => 'nullable|integer|min:1|max:20',
            ],
            ['foto.required' => 'Toma la fotografía del usuario antes de guardar.'],
            ['foto' => 'fotografía'],
        );

        $anterior = $this->id_usuario ? Prestatario::find($this->id_usuario) : null;
        $rutaFoto = $this->foto ? $this->foto->store('prestatarios', 'local') : $anterior?->foto;

        try {
            Prestatario::updateOrCreate(['id' => $this->id_usuario], [
                'numero_control' => $this->numero_control,
                'nombre_completo' => $this->nombre_completo,
                'tipo_usuario' => $this->tipo_usuario,
                'carrera_departamento' => $this->carrera_departamento ?: null,
                'semestre' => $this->semestre !== '' ? $this->semestre : null,
                'grupo' => $this->grupo ?: null,
                'telefono' => $this->telefono ?: null,
                'estado' => $this->estado,
                'foto' => $rutaFoto,
            ]);
        } catch (\Exception $e) {
            // No dejar en disco una foto nueva de un registro que no se guardó
            if ($this->foto) {
                Storage::disk('local')->delete($rutaFoto);
            }
            throw $e;
        }

        // Si se reemplazó la foto, borrar la anterior del disco
        if ($this->foto && $anterior?->foto) {
            Storage::disk('local')->delete($anterior->foto);
        }
        
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
        $this->foto = null;
        $this->fotoActual = $usuario->foto_url;
        $this->resetValidation();
        
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
                if ($usuario->foto) {
                    Storage::disk('local')->delete($usuario->foto);
                }
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