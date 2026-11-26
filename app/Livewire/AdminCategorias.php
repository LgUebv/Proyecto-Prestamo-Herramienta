<?php

namespace App\Livewire;

use App\Models\Categoria;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AdminCategorias extends Component
{
    use WithPagination;

    public $search = '';
    public $modalAbierto = false;

    public $id_categoria;
    public $nombre = '';
    public $prefijo = '';
    public $descripcion = '';

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
        $this->id_categoria = null;
        $this->nombre = '';
        $this->prefijo = '';
        $this->descripcion = '';
        $this->resetErrorBag();
    }

    public function guardar()
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
        $this->nombre = trim($this->nombre);
        $this->prefijo = strtoupper(trim($this->prefijo));

        $this->validate(
            [
                'nombre' => ['required', 'string', 'max:100', Rule::unique('categorias', 'nombre')->ignore($this->id_categoria)],
                'prefijo' => ['required', 'regex:/^[A-Z]{2,5}$/', Rule::unique('categorias', 'prefijo')->ignore($this->id_categoria)],
                'descripcion' => 'nullable|string|max:500',
            ],
            ['prefijo.regex' => 'El prefijo debe tener de 2 a 5 letras, sin números ni acentos (ej. MAN).'],
            ['nombre' => 'nombre', 'prefijo' => 'prefijo', 'descripcion' => 'descripción'],
        );

        Categoria::updateOrCreate(['id' => $this->id_categoria], [
            'nombre' => $this->nombre,
            'prefijo' => $this->prefijo,
            'descripcion' => $this->descripcion ?: null,
        ]);

        $this->mensajeExito = $this->id_categoria
            ? 'Categoría actualizada correctamente.'
            : 'Categoría registrada correctamente.';
        $this->cerrarModal();
    }

    public function editar($id)
    {
        $categoria = Categoria::findOrFail($id);
        $this->id_categoria = $categoria->id;
        $this->nombre = $categoria->nombre;
        $this->prefijo = $categoria->prefijo ?? '';
        $this->descripcion = $categoria->descripcion ?? '';
        $this->resetErrorBag();
        $this->modalAbierto = true;
    }

    public function borrar($id)
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';

        $categoria = Categoria::withCount('herramientas')->findOrFail($id);

        // Una categoría con herramientas no se borra: quedarían sin categoría
        if ($categoria->herramientas_count > 0) {
            $this->mensajeError = "La categoría \"{$categoria->nombre}\" tiene {$categoria->herramientas_count} herramienta(s) registrada(s) y no se puede eliminar. Cámbialas de categoría primero.";
            return;
        }

        $categoria->delete();
        $this->mensajeExito = "Categoría \"{$categoria->nombre}\" eliminada correctamente.";
    }

    public function render()
    {
        $categorias = Categoria::withCount('herramientas')
            ->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('prefijo', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.admin-categorias', compact('categorias'));
    }
}
