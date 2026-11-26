<div class="max-w-7xl mx-auto p-6 sm:px-6 lg:px-8 mt-8">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Categorías de Herramientas</h2>
    </x-slot>

    <div class="bg-white shadow-xl sm:rounded-lg p-6">
        @if ($mensajeExito)
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">{{ $mensajeExito }}</div>
        @endif
        @if ($mensajeError)
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">{{ $mensajeError }}</div>
        @endif

        <div class="flex justify-between mb-6">
            <input type="text" wire:model.live="search" placeholder="Buscar por nombre o prefijo..."
                   class="border-gray-300 rounded-md w-1/3 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            <button wire:click="abrirModal" class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow hover:bg-indigo-700 font-bold">
                + Nueva Categoría
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prefijo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Herramientas</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categorias as $categoria)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $categoria->nombre }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700">{{ $categoria->prefijo ?? 'HER' }}-</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $categoria->descripcion ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">{{ $categoria->herramientas_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-3">
                            <button wire:click="editar({{ $categoria->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                            <button wire:click="borrar({{ $categoria->id }})" wire:confirm="¿Estás seguro de que deseas eliminar esta categoría?" class="text-red-600 hover:text-red-900 font-bold">Borrar</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No se encontraron categorías.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $categorias->links() }}
        </div>
    </div>

    @if($modalAbierto)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-bold text-gray-800">{{ $id_categoria ? 'Editar Categoría' : 'Registrar Nueva Categoría' }}</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Categoría</label>
                    <input type="text" wire:model="nombre" placeholder="Ej. Herramienta Eléctrica" class="w-full mb-1 border-gray-300 rounded-md shadow-sm">
                    @error('nombre') <span class="block mb-2 text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prefijo del Código de Barras</label>
                    <input type="text" wire:model="prefijo" placeholder="Ej. ELE" maxlength="5" class="w-full mb-1 border-gray-300 rounded-md shadow-sm uppercase">
                    @error('prefijo') <span class="block mb-2 text-red-500 text-sm">{{ $message }}</span> @enderror
                    <p class="text-sm text-gray-500">De 2 a 5 letras. Las herramientas nuevas de esta categoría tendrán códigos como <span class="font-mono">{{ $prefijo ? strtoupper($prefijo) : 'ELE' }}-1</span>.
                        @if ($id_categoria) Si lo cambias, las herramientas ya registradas conservan su código actual. @endif
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
                    <textarea wire:model="descripcion" rows="3" placeholder="Ej. Taladros, esmeriles y equipo que se conecta a la corriente" class="w-full mb-1 border-gray-300 rounded-md shadow-sm"></textarea>
                    @error('descripcion') <span class="block mb-2 text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-3">
                <button wire:click="cerrarModal" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 shadow-sm font-medium">
                    Cancelar
                </button>
                <button wire:click="guardar" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 shadow-sm font-medium">
                    Guardar Categoría
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
