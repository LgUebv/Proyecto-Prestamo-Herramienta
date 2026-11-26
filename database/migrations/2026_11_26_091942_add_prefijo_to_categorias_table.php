<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el prefijo del código de barras a cada categoría (MAN-1, POD-1, MED-1…).
     * Antes el prefijo estaba fijo en el código según el id de la categoría.
     */
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('prefijo', 5)->nullable()->unique()->after('nombre');
        });

        // Categorías de siempre, con el mismo prefijo que se usaba antes por id
        $base = [
            1 => ['nombre' => 'Herramienta Manual', 'prefijo' => 'MAN'],
            2 => ['nombre' => 'Herramienta de Poder', 'prefijo' => 'POD'],
            3 => ['nombre' => 'Medición', 'prefijo' => 'MED'],
        ];

        if (DB::table('categorias')->count() === 0) {
            // Base nueva (p. ej. al desplegar): se crean las tres categorías
            foreach ($base as $id => $datos) {
                DB::table('categorias')->insert(['id' => $id, ...$datos, 'created_at' => now(), 'updated_at' => now()]);
            }
        } else {
            // Base existente: se conservan los códigos actuales
            foreach ($base as $id => $datos) {
                DB::table('categorias')->where('id', $id)->update(['prefijo' => $datos['prefijo']]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['prefijo']);
            $table->dropColumn('prefijo');
        });
    }
};
