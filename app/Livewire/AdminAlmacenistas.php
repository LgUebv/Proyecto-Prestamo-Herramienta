<?php

namespace App\Livewire;

use App\Models\Almacenista;
use App\Models\User;
use App\Models\Prestamo;
use App\Models\PrestamoDetalles;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
class AdminAlmacenistas extends Component
{
    use WithPagination;

    public $search = '';
    public $modalAbierto = false;

    public $id_almacenista;
    public $nombre = '';
    public $tipo = 'fijo';
    public $email = '';
    public $email_usuario = '';
    public $password = '';
    public $estado = 'activo';

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
        $this->id_almacenista = null;
        $this->nombre = '';
        $this->tipo = 'fijo';
        $this->email = '';
        $this->email_usuario = '';
        $this->password = '';
        $this->estado = 'activo';
        $this->resetErrorBag();
    }

    public function guardar()
    {
        $this->validate([
            'nombre' => 'required|string|max:150',
            'tipo' => 'required|in:fijo,temporal',
        ]);

        $almacenista = $this->id_almacenista ? Almacenista::findOrFail($this->id_almacenista) : null;

        if ($this->tipo === 'fijo') {
            // Al editar, el correo puede ser el mismo del usuario y la contraseña es opcional
            $this->validate([
                'email_usuario' => ['required', 'email', Rule::unique('users', 'email')->ignore($almacenista?->user_id)],
                'password' => $almacenista ? 'nullable|min:8' : 'required|min:8',
            ]);
        }

        try {
            if ($almacenista) {
                // Editar almacenista existente (el tipo no cambia al editar)
                $almacenista->update([
                    'nombre' => $this->nombre,
                    'email' => $this->email ?: null,
                    'estado' => $this->estado,
                ]);

                // Mantener sincronizada la cuenta de acceso
                if ($almacenista->user) {
                    $datosUsuario = ['name' => $this->nombre];

                    if ($almacenista->tipo === 'fijo') {
                        $datosUsuario['email'] = $this->email_usuario;

                        if (filled($this->password)) {
                            $datosUsuario['password'] = Hash::make($this->password);
                        }
                    }

                    $almacenista->user->update($datosUsuario);
                }
            } else {
                // Crear nuevo almacenista
                $almacenista = Almacenista::create([
                    'nombre' => $this->nombre,
                    'tipo' => $this->tipo,
                    'email' => $this->email ?: null,
                    'estado' => $this->estado,
                ]);

                // Crear usuario (fijo para loguear, temporal solo para recepciones)
                $email = $this->tipo === 'fijo'
                    ? $this->email_usuario
                    : "temporal_" . $almacenista->id . "@almacen.local";

                $password = $this->tipo === 'fijo'
                    ? Hash::make($this->password)
                    : Hash::make(uniqid());

                $usuario = User::create([
                    'name' => $this->nombre,
                    'email' => $email,
                    'password' => $password,
                ]);
                $almacenista->update(['user_id' => $usuario->id]);
            }

            $this->mensajeExito = '¡Almacenista guardado correctamente!';
            $this->cerrarModal();
        } catch (\Exception $e) {
            $this->mensajeError = 'Error al guardar: ' . $e->getMessage();
        }
    }

    public function editar($id)
    {
        $almacenista = Almacenista::findOrFail($id);
        $this->id_almacenista = $id;
        $this->nombre = $almacenista->nombre;
        $this->tipo = $almacenista->tipo;
        $this->email = $almacenista->email;
        $this->email_usuario = $almacenista->tipo === 'fijo' ? ($almacenista->user?->email ?? '') : '';
        $this->password = '';
        $this->estado = $almacenista->estado;
        $this->resetErrorBag();
        $this->modalAbierto = true;
    }

    public function borrar($id)
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        try {
            $almacenista = Almacenista::findOrFail($id);

            // Si su cuenta aparece en algún préstamo (registró, entregó o recibió), no se borra para conservar el historial
            $tieneHistorial = $almacenista->user_id && (
                Prestamo::where('user_id', $almacenista->user_id)->exists()
                || PrestamoDetalles::where('entregado_por_id', $almacenista->user_id)
                    ->orWhere('recibido_por_id', $almacenista->user_id)
                    ->exists()
            );

            if ($tieneHistorial) {
                $almacenista->update(['estado' => 'inactivo']);
                $this->mensajeExito = 'El almacenista tiene préstamos en su historial. Se ha marcado como INACTIVO y ya no aparecerá en los selectores.';
                return;
            }

            DB::transaction(function () use ($almacenista) {
                $userId = $almacenista->user_id;
                $almacenista->delete();

                if ($userId) {
                    DB::table('sessions')->where('user_id', $userId)->delete();
                    User::destroy($userId);
                }
            });

            $this->mensajeExito = 'Almacenista eliminado correctamente.';
        } catch (\Exception $e) {
            $this->mensajeError = 'Error al eliminar: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $almacenistas = Almacenista::where('nombre', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin-almacenistas', compact('almacenistas'));
    }
}
