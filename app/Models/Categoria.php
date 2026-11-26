<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nombre', 'prefijo', 'descripcion'];

    public function herramientas()
    {
        return $this->hasMany(Herramienta::class, 'categoria_id');
    }
}
