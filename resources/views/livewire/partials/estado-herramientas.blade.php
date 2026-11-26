{{-- Estado de cada herramienta del préstamo: buen estado o dañada (→ Mantenimiento) con nota --}}
<div class="space-y-3">
    @foreach ($herramientasModal as $h)
        @php $id = $h['detalle_id']; @endphp
        <div wire:key="estado-{{ $id }}" class="p-3 border rounded-md {{ !empty($estadoHerramientas[$id]['danada']) ? 'border-orange-300 bg-orange-50' : 'border-gray-200' }}">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $h['nombre'] }}</p>
                    <p class="text-xs font-mono text-gray-500">{{ $h['codigo'] }}</p>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="radio" wire:model.live="estadoHerramientas.{{ $id }}.danada" value="0" class="text-green-600">
                        <span>Buen estado</span>
                    </label>
                    <label class="inline-flex items-center gap-1 cursor-pointer">
                        <input type="radio" wire:model.live="estadoHerramientas.{{ $id }}.danada" value="1" class="text-orange-600">
                        <span class="font-semibold text-orange-700">Dañada</span>
                    </label>
                </div>
            </div>

            @if (!empty($estadoHerramientas[$id]['danada']))
                <div class="mt-2">
                    <input type="text" wire:model="estadoHerramientas.{{ $id }}.nota" maxlength="500"
                           placeholder="Describe el daño (ej. mango roto, no enciende)"
                           class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                    @error("estadoHerramientas.$id.nota") <span class="block mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
                    <p class="mt-1 text-xs text-orange-700">Se enviará a <strong>Mantenimiento</strong> y no se podrá prestar hasta que la regresen a Disponible desde Herramientas.</p>
                </div>
            @endif
        </div>
    @endforeach
</div>
