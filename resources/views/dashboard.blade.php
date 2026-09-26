<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bienvenido al Sistema de Préstamo de Herramientas') }}
        </h2>
    </x-slot>

    @php
        $maxMes = max(1, $prestamosPorMes->max('total'));
        $maxPrestadas = max(1, $masPrestadas->max('total'));
        $maxCategoria = max(1, $porCategoria->max('total'));
        $diferenciaMes = $prestamosEsteMes - $prestamosMesAnterior;
    @endphp

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <p class="text-gray-600">
                ¡Hola, <span class="font-semibold text-gray-900">{{ auth()->user()->name }}</span>! Este es el resumen actual del almacén.
            </p>

            {{-- ============ Indicadores principales ============ --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-600">Herramientas registradas</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($totalHerramientas) }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $disponibilidad['disponible']['total'] }} disponibles ahora</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-600">Préstamos activos</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($prestamosActivos) }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $disponibilidad['prestada']['total'] }} {{ $disponibilidad['prestada']['total'] === 1 ? 'herramienta' : 'herramientas' }} fuera del almacén</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-600">Préstamos vencidos</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($prestamosVencidos) }}</p>
                    @if ($prestamosVencidos > 0)
                        <p class="mt-1 text-sm font-medium text-[#b42f2f] flex items-center gap-1">
                            <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                            Requieren atención
                        </p>
                    @else
                        <p class="mt-1 text-sm font-medium text-[#006300] flex items-center gap-1">
                            <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>
                            Todo al corriente
                        </p>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <p class="text-sm text-gray-600">Préstamos este mes</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($prestamosEsteMes) }}</p>
                    <p class="mt-1 text-sm text-gray-500">
                        @if ($diferenciaMes > 0)
                            {{ $diferenciaMes }} más que el mes pasado
                        @elseif ($diferenciaMes < 0)
                            {{ abs($diferenciaMes) }} menos que el mes pasado
                        @else
                            Igual que el mes pasado
                        @endif
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- ============ Préstamos por mes (columnas) ============ --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6 lg:col-span-2">
                    <h3 class="font-semibold text-gray-900">Préstamos por mes</h3>
                    <p class="text-sm text-gray-500">Últimos 6 meses</p>

                    <div class="mt-6 flex items-end h-52 border-b border-gray-300" role="img"
                         aria-label="Préstamos por mes: {{ $prestamosPorMes->map(fn ($m) => $m['nombre'].' '.$m['total'])->join(', ') }}">
                        @foreach ($prestamosPorMes as $mes)
                            <div class="group relative flex-1 h-full flex flex-col justify-end items-center outline-none" tabindex="0">
                                <span class="mb-1 text-xs font-medium text-gray-700 tabular-nums">{{ $mes['total'] }}</span>
                                <div class="w-6 rounded-t bg-[#2a78d6] group-hover:bg-[#256abf] group-focus:bg-[#256abf]"
                                     style="height: {{ $mes['total'] > 0 ? max(2, $mes['total'] * 85 / $maxMes) : 0 }}%"></div>

                                <div class="pointer-events-none absolute bottom-full mb-1 hidden group-hover:block group-focus:block z-10 whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-xs text-white shadow-lg">
                                    <span class="block text-sm font-semibold">{{ $mes['total'] }} {{ $mes['total'] === 1 ? 'préstamo' : 'préstamos' }}</span>
                                    <span class="text-gray-300">{{ $mes['nombre'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex mt-2">
                        @foreach ($prestamosPorMes as $mes)
                            <span class="flex-1 text-center text-xs text-gray-500">{{ $mes['etiqueta'] }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- ============ Disponibilidad del inventario (barra apilada) ============ --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900">Estado del inventario</h3>
                    <p class="text-sm text-gray-500">{{ $totalHerramientas }} herramientas en total</p>

                    @if ($totalHerramientas > 0)
                        <div class="mt-6 flex h-6 gap-[2px]" role="img"
                             aria-label="Estado del inventario: {{ $disponibilidad->map(fn ($d) => $d['etiqueta'].' '.$d['total'])->join(', ') }}">
                            @foreach ($disponibilidad->where('total', '>', 0) as $estado)
                                <div class="group relative h-full first:rounded-l last:rounded-r outline-none" tabindex="0"
                                     style="flex: {{ $estado['total'] }} 1 0%; background-color: {{ $estado['color'] }}">
                                    <div class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block group-focus:block z-10 whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-xs text-white shadow-lg">
                                        <span class="block text-sm font-semibold">{{ $estado['total'] }} ({{ $estado['porcentaje'] }}%)</span>
                                        <span class="text-gray-300">{{ $estado['etiqueta'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <ul class="mt-6 space-y-3">
                            @foreach ($disponibilidad as $estado)
                                <li class="flex items-center gap-3 text-sm">
                                    <span class="size-3 rounded-sm shrink-0" style="background-color: {{ $estado['color'] }}"></span>
                                    <span class="text-gray-700 flex-1">{{ $estado['etiqueta'] }}</span>
                                    <span class="font-semibold text-gray-900 tabular-nums">{{ $estado['total'] }}</span>
                                    <span class="w-10 text-right text-gray-500 tabular-nums">{{ $estado['porcentaje'] }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-6 text-sm text-gray-500">Aún no hay herramientas registradas.</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- ============ Herramientas más prestadas (barras horizontales) ============ --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900">Herramientas más prestadas</h3>
                    <p class="text-sm text-gray-500">Veces que se ha prestado cada herramienta</p>

                    @if ($masPrestadas->isNotEmpty())
                        <ul class="mt-6 space-y-4">
                            @foreach ($masPrestadas as $item)
                                <li>
                                    <p class="text-sm text-gray-700 truncate" title="{{ $item->nombre }}">{{ $item->nombre }}</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <div class="h-4 rounded-r bg-[#2a78d6]" style="width: {{ max(1, $item->total * 85 / $maxPrestadas) }}%"></div>
                                        <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ $item->total }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-6 text-sm text-gray-500">Todavía no hay préstamos registrados.</p>
                    @endif
                </div>

                {{-- ============ Herramientas por categoría (barras horizontales) ============ --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900">Herramientas por categoría</h3>
                    <p class="text-sm text-gray-500">Piezas registradas en cada categoría</p>

                    @if ($porCategoria->isNotEmpty())
                        <ul class="mt-6 space-y-4">
                            @foreach ($porCategoria as $categoria)
                                <li>
                                    <p class="text-sm text-gray-700 truncate" title="{{ $categoria->nombre }}">{{ $categoria->nombre }}</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        @if ($categoria->total > 0)
                                            <div class="h-4 rounded-r bg-[#2a78d6]" style="width: {{ max(1, $categoria->total * 85 / $maxCategoria) }}%"></div>
                                        @endif
                                        <span class="text-sm font-semibold text-gray-900 tabular-nums">{{ $categoria->total }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-6 text-sm text-gray-500">Aún no hay categorías registradas.</p>
                    @endif
                </div>
            </div>

            {{-- ============ Préstamos próximos a vencer (tabla) ============ --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-baseline justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Préstamos activos por vencer</h3>
                        <p class="text-sm text-gray-500">Los 5 más próximos a su fecha límite</p>
                    </div>
                    <a href="{{ route('admin.prestamos') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap">Ver todos &rarr;</a>
                </div>

                @if ($proximosVencer->isNotEmpty())
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th class="py-2 pr-4 font-medium">Prestatario</th>
                                    <th class="py-2 pr-4 font-medium text-right">Herramientas</th>
                                    <th class="py-2 pr-4 font-medium">Fecha límite</th>
                                    <th class="py-2 font-medium">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($proximosVencer as $prestamo)
                                    @php $limite = \Carbon\Carbon::parse($prestamo->fecha_limite); @endphp
                                    <tr>
                                        <td class="py-3 pr-4 text-gray-900">{{ $prestamo->prestatario?->nombre_completo ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-right text-gray-700 tabular-nums">{{ $prestamo->detalles_count }}</td>
                                        <td class="py-3 pr-4 text-gray-700 tabular-nums whitespace-nowrap">{{ $limite->format('d/m/Y H:i') }}</td>
                                        <td class="py-3 whitespace-nowrap">
                                            @if ($limite->isPast())
                                                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-[#b42f2f]">
                                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                                                    Vencido {{ $limite->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd"/></svg>
                                                    Vence {{ $limite->diffForHumans() }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500">No hay préstamos activos en este momento.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
