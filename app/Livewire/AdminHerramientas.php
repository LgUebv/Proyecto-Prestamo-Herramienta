<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Herramienta;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
class AdminHerramientas extends Component
{
    use WithPagination;

    // Variables del buscador y CRUD
    public $search = '';
    public $modalAbierto = false;
    public $id_herramienta, $codigo_barras, $nombre, $marca, $estado_fisico, $disponibilidad, $categoria_id;
    public $cantidad = 1;

    // Variables exclusivas para el QR
    public $modalQrAbierto = false;
    public $herramientaSeleccionada = null;

    public function updatingSearch() { $this->resetPage(); }

    // --- FUNCIONES DEL CRUD DE HERRAMIENTAS ---
    public function abrirModal() { $this->resetInput(); $this->modalAbierto = true; }
    public function cerrarModal() { $this->modalAbierto = false; }
    
    public function resetInput() {
        $this->id_herramienta = null; $this->codigo_barras = ''; $this->nombre = ''; 
        $this->marca = ''; $this->estado_fisico = ''; $this->disponibilidad = 'disponible';
        $this->categoria_id = null; $this->cantidad = 1;
    }

    public function guardar()
    {
        // 1. Validamos que siempre elijan una categoría
        $this->validate([
            'nombre' => 'required',
            'cantidad' => 'required|integer|min:1',
            'categoria_id' => 'required'
        ]);

        // 2. LÓGICA DE PREFIJO: Basado estrictamente en la categoría
        $prefijo = match ((int) $this->categoria_id) {
            1 => 'MAN', // 1 = Herramienta Manual
            2 => 'POD', // 2 = Herramienta de Poder
            3 => 'MED', // 3 = Medición
            default => 'HER', // HER = Genérico por si agregan nuevas categorías después
        };

        // 3. Si estamos EDITANDO
        if ($this->id_herramienta) {
            $herramienta = Herramienta::find($this->id_herramienta);
            
            $herramienta->update([
                'nombre' => $this->nombre,
                'marca' => $this->marca,
                'disponibilidad' => $this->disponibilidad,
                'categoria_id' => $this->categoria_id,
            ]);
        } 
        // 4. Si estamos CREANDO (Lote)
        else {
            // Buscamos todas las herramientas que ya usen el prefijo de esta categoría
            $codigosExistentes = Herramienta::where('codigo_barras', 'LIKE', $prefijo . '-%')
                                            ->pluck('codigo_barras');
            
            // Calculamos cuál fue el último número utilizado para esta categoría
            $maxNumero = 0;
            foreach ($codigosExistentes as $codigo) {
                $partes = explode('-', $codigo); // Dividimos "MAN-15" en ["MAN", "15"]
                $numero = (int) end($partes);    // Tomamos el "15"
                
                if ($numero > $maxNumero) {
                    $maxNumero = $numero;
                }
            }

            // Nuestro lote empezará a contar a partir del número máximo
            $contadorSecuencia = $maxNumero + 1;

            for ($i = 1; $i <= $this->cantidad; $i++) {
                
                // Armamos el código final (ej. POD-1, POD-2...)
                $codigoFinal = $prefijo . '-' . $contadorSecuencia;

                Herramienta::create([
                    'codigo_barras' => $codigoFinal,
                    'nombre' => $this->nombre,
                    'marca' => $this->marca,
                    'disponibilidad' => $this->disponibilidad,
                    'categoria_id' => $this->categoria_id,
                ]);

                $contadorSecuencia++; 
            }
        }
        
        $this->cerrarModal();
    }

    public function editar($id)
    {
        $h = Herramienta::findOrFail($id);
        $this->id_herramienta = $id;
        $this->codigo_barras = $h->codigo_barras;
        $this->nombre = $h->nombre;
        $this->marca = $h->marca;
        $this->estado_fisico = $h->estado_fisico;
        $this->disponibilidad = $h->disponibilidad;
        $this->categoria_id = $h->categoria_id;
        $this->modalAbierto = true;
    }

    public function borrar($id) { Herramienta::find($id)->delete(); }

    // --- FUNCIONES DEL CÓDIGO QR ---
    public function mostrarQr($id)
    {
        $this->herramientaSeleccionada = Herramienta::find($id);
        $this->modalQrAbierto = true;
    }

    public function cerrarModalQr()
    {
        $this->modalQrAbierto = false;
        $this->herramientaSeleccionada = null;
    }

    public function generarQrSvg($codigo)
    {
        // Usa la librería nativa de Jetstream (BaconQrCode)
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200, 1),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        
        return $writer->writeString($codigo);
    }

    public function generarCodigoPng($codigo)
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        
        // El "1" asegura que el grosor base de las líneas sea de 1 píxel exacto.
        $pngData = $generator->getBarcode($codigo, $generator::TYPE_CODE_128, 1, 60);
        
        return base64_encode($pngData);
    }

    // --- RENDER DE LA VISTA ---
    public function render()
    {
        $herramientas = Herramienta::where('nombre', 'like', '%'.$this->search.'%')
                        ->orWhere('codigo_barras', 'like', '%'.$this->search.'%')
                        ->orderBy('id')
                        ->paginate(10);
                        
        return view('livewire.admin-herramientas', compact('herramientas'));
    }
}