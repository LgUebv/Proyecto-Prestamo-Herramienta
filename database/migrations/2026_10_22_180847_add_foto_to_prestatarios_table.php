<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prestatarios', function (Blueprint $table) {
            // Ruta de la foto en el disco privado (storage/app/private)
            $table->string('foto')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestatarios', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
