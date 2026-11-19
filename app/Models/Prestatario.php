<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestatario extends Model
{
    protected $table = 'prestatarios';

    protected $fillable = [
        'numero_control',
        'nombre_completo',
        'tipo_usuario',
        'carrera_departamento',
        'semestre',
        'grupo',
        'telefono',
        'estado',
        'foto'
    ];

    // Número de control para quien no tiene uno (trabajadores): EXT-<marca de tiempo>, sin repetirse
    public static function generarNumeroExterno(): string
    {
        $numero = time();
        while (static::where('numero_control', 'EXT-' . $numero)->exists()) {
            $numero++;
        }

        return 'EXT-' . $numero;
    }

    // URL protegida (requiere sesión) para mostrar la foto del prestatario
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? route('prestatarios.foto', $this) . '?v=' . $this->updated_at?->timestamp : null;
    }
}
