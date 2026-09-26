<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Livewire\MisPrestamos;
use App\Livewire\AdminPrestamos;
use App\Livewire\AdminHerramientas;
use App\Livewire\AdminUsuarios;
use App\Livewire\NuevoPrestamo;
use App\Livewire\AdminInventario;

Route::get('/', function () {
    // Disponibilidad por categoría para la página de inicio
    $categorias = DB::table('categorias')
        ->leftJoin('herramientas', 'herramientas.categoria_id', '=', 'categorias.id')
        ->selectRaw("categorias.nombre, COUNT(herramientas.id) as total, SUM(CASE WHEN herramientas.disponibilidad = 'disponible' THEN 1 ELSE 0 END) as disponibles")
        ->groupBy('categorias.id', 'categorias.nombre')
        ->orderBy('categorias.nombre')
        ->get();

    return view('welcome', compact('categorias'));
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

// Ruta para usuarios normales
Route::get('/mis-prestamos', MisPrestamos::class)->name('mis.prestamos');

// Rutas para administradores
Route::get('/admin/prestamos', AdminPrestamos::class)->name('admin.prestamos');
Route::get('/admin/prestamos/nuevo', NuevoPrestamo::class)->name('prestamos.nuevo');
Route::get('/admin/herramientas', AdminHerramientas::class)->name('admin.herramientas');
Route::get('/admin/usuarios', AdminUsuarios::class)->name('admin.usuarios');
Route::get('/admin/inventario', AdminInventario::class)->name('admin.inventario');
});
