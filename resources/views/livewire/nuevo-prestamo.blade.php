<div class="max-w-7xl mx-auto p-6 sm:px-6 lg:px-8 mt-8">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registrar Nuevo Préstamo</h2>
    </x-slot>

    <div class="bg-white shadow-xl sm:rounded-lg p-6">
        
        @if($mensajeError)
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">{{ $mensajeError }}</div>
        @endif
        @if($mensajeExito)
            <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">{{ $mensajeExito }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="col-span-1 bg-gray-50 p-4 rounded-lg border">
                
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-semibold text-gray-700">1. Prestatario</label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="es_trabajador" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4">
                            <span class="ml-2 text-xs font-bold text-indigo-600">Es trabajador externo</span>
                        </label>
                    </div>
                    
                    @if($es_trabajador)
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <label class="block text-xs text-gray-700 font-bold mb-1 text-yellow-800">Nombre Completo del Trabajador:</label>
                            <input type="text" wire:model="nombre_trabajador" 
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-yellow-500 focus:border-yellow-500" 
                                placeholder="Ej. Juan Pérez" autofocus>
                        </div>
                    @else
                        <label class="block text-xs font-semibold text-gray-500 mb-2">Escanea credencial o ingresa número</label>
                        
                        @if(!$prestatario && !$esNuevoRegistro)
                            <input type="text" wire:model="numeroControl" wire:keydown.enter="buscarPrestatario"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                                placeholder="Ingresa el no. de control y presiona Enter" autofocus>
                        @else
                            <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-md">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-bold text-indigo-600">ID: {{ $numeroControl }}</span>
                                    <button type="button" wire:click="limpiarPrestatario" class="text-red-500 text-sm hover:underline font-bold">Cambiar</button>
                                </div>
                                
                                @if($esNuevoRegistro)
                                    <div class="mt-2">
                                        <label class="block text-xs text-gray-700 font-bold text-indigo-800">¡Usuario Nuevo! Ingresa su nombre:</label>
                                        <input type="text" wire:model="nombreCompleto" 
                                            class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm" placeholder="Nombre completo" autofocus>
                                    </div>
                                @else
                                    <p class="text-sm font-bold text-indigo-800">{{ $nombreCompleto }}</p>
                                    <p class="text-xs text-indigo-600">{{ $prestatario->tipo_usuario ?? '' }}</p>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>

                <div class="mb-6 p-4 bg-white border rounded-md shadow-sm">
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">2. Datos del Préstamo</h4>
                    
                    <div class="mb-3">
                        <label class="block text-sm text-gray-700">Materia / Práctica</label>
                        <input type="text" wire:model="materia" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="mb-3">
                        <span class="block text-sm text-gray-700">Fecha y Hora Límite</span>
                        <p class="mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-md text-sm text-gray-800">
                            @php $fechaLimite = $this->calcularFechaLimite(); @endphp
                            {{ $fechaLimite->isToday() ? 'Hoy' : 'Mañana' }}, {{ $fechaLimite->format('d/m/Y') }} a las 8:00 p.m.
                        </p>
                    </div>

                    <div class="mt-4 flex items-start">
                        <input type="checkbox" wire:model="aceptoTerminos" id="terminos" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm">
                        <label for="terminos" class="ml-2 text-sm text-gray-600">Acepta términos de uso.</label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">3. Escanear Herramienta</label>
                    <input type="text" wire:model="codigoBarras" wire:keydown.enter="agregarAlCarrito"
                        class="w-full border-gray-300 rounded-md shadow-sm disabled:bg-gray-200" 
                        placeholder="Código QR y Enter" {{ (!$es_trabajador && !$prestatario && !$esNuevoRegistro) ? 'disabled' : '' }}>
                </div>

            </div>

            <div class="col-span-1 md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Herramientas a Prestar ({{ count($carrito) }})</h3>
                
                <div class="bg-white border rounded-lg overflow-hidden shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Herramienta</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($carrito as $index => $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $item['codigo_barras'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <span class="font-semibold">{{ $item['nombre'] }}</span> <br>
                                        <span class="text-xs text-gray-500">{{ $item['marca'] ?? '' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="quitarDelCarrito({{ $index }})" class="text-red-600 hover:text-red-900 font-bold">Quitar</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">Escanea una herramienta para agregarla a la lista.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="confirmarPrestamo" 
                            class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-md shadow hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            {{ empty($carrito) || (!$es_trabajador && !$prestatario && !$esNuevoRegistro) || ($es_trabajador && empty($nombre_trabajador)) || empty($materia) || !$aceptoTerminos ? 'disabled' : '' }}>
                        Confirmar Préstamo
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>