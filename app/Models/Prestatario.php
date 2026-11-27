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

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'prestatario_id');
    }

    /**
     * Regla de morosidad: si tiene préstamos vencidos sin devolver, devuelve el mensaje
     * que explica por qué no puede pedir otro préstamo; si no, devuelve null.
     */
    public function motivoBloqueoPorMorosidad(): ?string
    {
        $vencidos = $this->prestamos()->vencidos()->orderBy('fecha_limite')->get(['id', 'fecha_limite']);

        if ($vencidos->isEmpty()) {
            return null;
        }

        $detalle = $vencidos->map(fn ($p) => "#{$p->id} (venció el " . \Carbon\Carbon::parse($p->fecha_limite)->format('d/m/Y H:i') . ')')
            ->implode(', ');

        return "{$this->nombre_completo} tiene " . ($vencidos->count() === 1 ? 'un préstamo vencido' : "{$vencidos->count()} préstamos vencidos")
            . " sin devolver: {$detalle}. Debe devolver las herramientas antes de pedir un nuevo préstamo.";
    }

    // URL protegida (requiere sesión) para mostrar la foto del prestatario
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? route('prestatarios.foto', $this) . '?v=' . $this->updated_at?->timestamp : null;
    }
}
