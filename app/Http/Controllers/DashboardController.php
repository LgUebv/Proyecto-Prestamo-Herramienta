<?php

namespace App\Http\Controllers;

use App\Models\Herramienta;
use App\Models\Prestamo;
use App\Models\PrestamoDetalles;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Colores fijos por estado (paleta categórica validada para daltonismo)
    private const DISPONIBILIDAD = [
        'disponible' => ['etiqueta' => 'Disponibles', 'color' => '#2a78d6'],
        'prestada' => ['etiqueta' => 'Prestadas', 'color' => '#eb6834'],
        'mantenimiento' => ['etiqueta' => 'En mantenimiento', 'color' => '#1baf7a'],
    ];

    public function __invoke()
    {
        // --- Indicadores principales ---
        $totalHerramientas = Herramienta::count();
        $conteoDisponibilidad = Herramienta::selectRaw('disponibilidad, COUNT(*) as total')
            ->groupBy('disponibilidad')
            ->pluck('total', 'disponibilidad');

        $prestamosActivos = Prestamo::where('estado_prestamo', 'activo')->count();
        $prestamosVencidos = Prestamo::where('estado_prestamo', 'activo')
            ->where('fecha_limite', '<', now())
            ->count();

        // --- Disponibilidad del inventario (parte de un todo) ---
        $disponibilidad = collect(self::DISPONIBILIDAD)->map(fn ($estado, $clave) => [
            ...$estado,
            'total' => (int) ($conteoDisponibilidad[$clave] ?? 0),
            'porcentaje' => $totalHerramientas > 0
                ? round(($conteoDisponibilidad[$clave] ?? 0) * 100 / $totalHerramientas)
                : 0,
        ]);

        // --- Préstamos por mes (últimos 6 meses, incluidos los meses sin préstamos) ---
        $inicio = now()->startOfMonth()->subMonths(5);
        $porMes = Prestamo::where('fecha_prestamo', '>=', $inicio)
            ->pluck('fecha_prestamo')
            ->countBy(fn ($fecha) => Carbon::parse($fecha)->format('Y-m'));

        $prestamosPorMes = collect(range(0, 5))->map(function ($i) use ($inicio, $porMes) {
            $mes = $inicio->copy()->addMonths($i);

            return [
                'etiqueta' => ucfirst($mes->translatedFormat('M')),
                'nombre' => ucfirst($mes->translatedFormat('F Y')),
                'total' => $porMes[$mes->format('Y-m')] ?? 0,
            ];
        });

        $prestamosEsteMes = $prestamosPorMes->last()['total'];
        $prestamosMesAnterior = $prestamosPorMes->get(4)['total'];

        // --- Herramientas más prestadas (agrupadas por nombre) ---
        $masPrestadas = PrestamoDetalles::join('herramientas', 'herramientas.id', '=', 'prestamo_detalles.herramienta_id')
            ->selectRaw('herramientas.nombre, COUNT(*) as total')
            ->groupBy('herramientas.nombre')
            ->orderByDesc('total')
            ->orderBy('herramientas.nombre')
            ->limit(5)
            ->get();

        // --- Herramientas por categoría ---
        $porCategoria = DB::table('categorias')
            ->leftJoin('herramientas', 'herramientas.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nombre, COUNT(herramientas.id) as total')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            ->orderBy('categorias.nombre')
            ->get();

        // --- Préstamos activos más próximos a vencer ---
        $proximosVencer = Prestamo::with('prestatario')
            ->withCount('detalles')
            ->where('estado_prestamo', 'activo')
            ->orderBy('fecha_limite')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalHerramientas',
            'prestamosActivos',
            'prestamosVencidos',
            'prestamosEsteMes',
            'prestamosMesAnterior',
            'disponibilidad',
            'prestamosPorMes',
            'masPrestadas',
            'porCategoria',
            'proximosVencer',
        ));
    }
}
