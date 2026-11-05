<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Almacenista extends Model
{
    protected $table = 'almacenistas';

    protected $fillable = [
        'nombre',
        'tipo',
        'email',
        'user_id',
        'estado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
