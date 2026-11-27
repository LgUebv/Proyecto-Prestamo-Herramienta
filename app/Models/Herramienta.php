<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Herramienta extends Model
{
    protected $table = 'herramientas';

    protected $fillable = [
        'codigo_barras',
        'nombre',
        'marca',
        'descripcion_equipo',
        'categoria_id',
        'estado_fisico',
        'disponibilidad',
        'observaciones'
    ];

    /**
     * Resumen agrupado por nombre y marca: stock total, disponibles, prestadas y en mantenimiento.
     */
    public function scopeResumenInventario($query)
    {
        return $query->selectRaw("
                nombre,
                marca,
                COUNT(*) as total_piezas,
                SUM(CASE WHEN disponibilidad = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN disponibilidad = 'prestada' THEN 1 ELSE 0 END) as prestadas,
                SUM(CASE WHEN disponibilidad = 'mantenimiento' THEN 1 ELSE 0 END) as en_mantenimiento
            ")
            ->groupBy('nombre', 'marca')
            ->orderBy('nombre')
            ->orderBy('marca');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
