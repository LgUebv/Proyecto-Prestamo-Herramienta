<div class="max-w-7xl mx-auto p-6 sm:px-6 lg:px-8 mt-8">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Control y Registros de Préstamos</h2>
    </x-slot>

    @if($mensajeExito)
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
            {{ $mensajeExito }}
        </div>
    @endif
    @if($mensajeError)
        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
            {{ $mensajeError }}
        </div>
    @endif

    <div class="bg-white shadow-xl sm:rounded-lg p-6">

       <div class="flex flex-col lg:flex-row justify-between items-center mb-6 space-y-4 lg:space-y-0">
            
            <div class="w-full lg:w-1/3">
                <input type="text" wire:model.live="search" placeholder="Buscar por nombre o número de control..." class="border-gray-300 rounded-md w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div class="flex flex-col sm:flex-row w-full lg:w-auto space-y-2 sm:space-y-0 sm:space-x-3 justify-end">
                
                <a href="{{ route('prestamos.nuevo') }}" class="bg-indigo-600 text-white px-4 py-2 rounded shadow-sm hover:bg-indigo-700 font-bold flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Nuevo Préstamo
                </a>

                <button wire:click="exportarExcel" class="bg-green-600 text-white px-4 py-2 rounded shadow-sm hover:bg-green-700 font-bold flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Exportar Excel
                </button>

                <button wire:click="exportarPdf" class="bg-red-600 text-white px-4 py-2 rounded shadow-sm hover:bg-red-700 font-bold flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Exportar PDF
                </button>
                
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4 mb-6 bg-gray-50 p-4 rounded-lg border">
            <div class="w-full md:w-1/3">
                <label class="block text-sm font-bold text-gray-700 mb-1">Desde Fecha:</label>
                <input type="date" wire:model.live="fecha_inicio" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>

            <div class="w-full md:w-1/3">
                <label class="block text-sm font-bold text-gray-700 mb-1">Hasta Fecha:</label>
                <input type="date" wire:model.live="fecha_fin" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>

            <div class="w-full md:w-auto">
                <button wire:click="limpiarFiltros" class="w-full bg-gray-300 text-gray-800 px-6 py-2 rounded shadow hover:bg-gray-400 font-bold text-sm transition-colors">
                    Limpiar Filtros
                </button>
            </div>
        </div>

        <div class="overflow-x-auto border rounded-lg shadow-sm mb-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Prestatario</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Proyecto</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Herramientas</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Firmas Almacén</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Salida</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($prestamos as $prestamo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $prestamo->prestatario->nombre_completo ?? 'N/A' }}</div>
                            <div class="text-xs font-semibold text-gray-500">{{ $prestamo->prestatario->numero_control ?? '' }}</div>
                        </td>
                        <td class="px-4 py-4 text-sm font-semibold text-gray-700">{{ $prestamo->materia }}</td>
                        <td class="px-4 py-4 text-sm text-gray-700">
                            <ul class="list-disc list-inside">
                                @foreach($prestamo->detalles as $detalle)
                                    <li>{{ $detalle->cantidad }}x {{ $detalle->herramienta->nombre ?? 'Eliminada' }} <span class="text-xs text-gray-400">({{ $detalle->herramienta->codigo_barras ?? 'N/A' }})</span></li>
                                @endforeach
                            </ul>
                        </td>
                        
                        <td class="px-4 py-4 text-xs">
                            @php $primerDetalle = $prestamo->detalles->first(); @endphp
                            @if($primerDetalle)
                                <div class="mb-1">
                                    <span class="font-bold text-indigo-700">Entregó:</span> 
                                    <span class="text-gray-700">{{ $primerDetalle->entregadoPor->name ?? 'Sistema' }}</span>
                                </div>
                                <div>
                                    <span class="font-bold text-teal-700">Recibió:</span> 
                                    @if($primerDetalle->recibidoPor)
                                        <span class="text-gray-700">{{ $primerDetalle->recibidoPor->name }}</span>
                                    @else
                                        <span class="text-yellow-600 font-bold bg-yellow-100 px-1 rounded">Pendiente</span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-4 text-sm text-gray-700 font-semibold">
                            {{ \Carbon\Carbon::parse($prestamo->fecha_prestamo)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($prestamo->estado_prestamo == 'activo' && is_null($prestamo->fecha_devolucion_real))
                                <button wire:click="abrirModalDevolucion({{ $prestamo->id }})" 
                                        class="bg-green-500 text-white px-3 py-1 rounded shadow hover:bg-green-600 text-sm font-bold w-full mb-1">
                                    Devuelto
                                </button>
                                <div class="text-xs text-red-600 font-bold">
                                    Límite: {{ \Carbon\Carbon::parse($prestamo->fecha_limite)->format('d/m/Y H:i') }}
                                </div>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-gray-200 text-gray-800">
                                    Finalizado: {{ \Carbon\Carbon::parse($prestamo->fecha_devolucion_real)->format('d/m/Y H:i') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 font-bold text-lg">
                            No se encontraron préstamos con estos filtros.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $prestamos->links() }}
        </div>
    </div>

    @if($modalDevolucionAbierto)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Confirmar Devolución</h3>
                    <button wire:click="cerrarModalDevolucion" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
                </div>

                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">
                        Por favor, confirma que estás recibiendo las herramientas en buen estado. Esta acción las regresará al inventario disponible.
                    </p>
                <label class="block text-sm font-bold text-indigo-800 mb-2">¿Qué almacenista recibe el equipo?</label>

<select wire:model.live="almacenista_recibe_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    <option value="">Selecciona al almacenista...</option>
    @foreach($almacenistas->groupBy('tipo') as $tipo => $grupo)
        <optgroup label="{{ $tipo === 'fijo' ? 'Fijos' : 'Temporales' }}">
            @foreach($grupo as $almacenista)
                <option value="{{ $almacenista->id }}">{{ $almacenista->nombre }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
@if($almacenistas->isEmpty())
    <span class="text-red-600 text-xs mt-1 block">No hay almacenistas activos. Regístralos en la sección Almacenistas.</span>
@endif

@error('almacenista_recibe_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-3">
                    <button wire:click="cerrarModalDevolucion" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 font-medium">Cancelar</button>
                    <button wire:click="confirmarDevolucion" 
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 font-medium disabled:opacity-50"
                            {{ empty($almacenista_recibe_id) ? 'disabled' : '' }}>
                        Confirmar y Devolver
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>