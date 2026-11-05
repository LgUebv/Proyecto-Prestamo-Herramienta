<div class="max-w-7xl mx-auto p-6 sm:px-6 lg:px-8 mt-8">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Directorio de Almacenistas</h2>
    </x-slot>

    <div class="bg-white shadow-xl sm:rounded-lg p-6">
        @if ($mensajeExito)
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">{{ $mensajeExito }}</div>
        @endif
        @if ($mensajeError)
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">{{ $mensajeError }}</div>
        @endif

        <div class="flex justify-between mb-6">
            <input type="text" wire:model.live="search" placeholder="Buscar por nombre o contacto..."
                   class="border-gray-300 rounded-md w-1/3 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            <button wire:click="abrirModal" class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow hover:bg-indigo-700 font-bold">
                + Nuevo Almacenista
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acceso / Contacto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($almacenistas as $almacenista)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $almacenista->nombre }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $almacenista->tipo === 'fijo' ? 'Fijo' : 'Temporal' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if ($almacenista->tipo === 'fijo')
                                {{ $almacenista->user?->email ?? 'N/A' }}
                            @else
                                {{ $almacenista->email ?? 'Sin acceso al sistema' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $almacenista->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($almacenista->estado) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-3">
                            <button wire:click="editar({{ $almacenista->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                            <button wire:click="borrar({{ $almacenista->id }})" wire:confirm="¿Estás seguro de que deseas eliminar a este almacenista? Esta acción no se puede deshacer." class="text-red-600 hover:text-red-900 font-bold">Borrar</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No se encontraron almacenistas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $almacenistas->links() }}
        </div>
    </div>

    @if($modalAbierto)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-bold text-gray-800">{{ $id_almacenista ? 'Editar Registro de Almacenista' : 'Registrar Nuevo Almacenista' }}</h3>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                    <input type="text" wire:model="nombre" placeholder="Ej. Juan Pérez" class="w-full mb-1 border-gray-300 rounded-md shadow-sm">
                    @error('nombre') <span class="block mb-2 text-red-500 text-sm">{{ $message }}</span> @enderror

                    <label class="block mt-2 text-sm font-medium text-gray-700 mb-1">Tipo de Almacenista</label>
                    <select wire:model.live="tipo" class="w-full mb-1 border-gray-300 rounded-md shadow-sm disabled:bg-gray-100" @disabled($id_almacenista)>
                        <option value="fijo">Fijo (inicia sesión en el sistema)</option>
                        <option value="temporal">Temporal (solo recibe devoluciones)</option>
                    </select>
                    @error('tipo') <span class="block mb-2 text-red-500 text-sm">{{ $message }}</span> @enderror

                    <label class="block mt-2 text-sm font-medium text-gray-700 mb-1">Estado en el Sistema</label>
                    <select wire:model="estado" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div>
                    @if ($tipo === 'fijo')
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo (para iniciar sesión)</label>
                        <input type="email" wire:model="email_usuario" placeholder="Ej. juan@almacen.com" class="w-full mb-1 border-gray-300 rounded-md shadow-sm">
                        @error('email_usuario') <span class="block mb-2 text-red-500 text-sm">{{ $message }}</span> @enderror

                        <label class="block mt-2 text-sm font-medium text-gray-700 mb-1">
                            {{ $id_almacenista ? 'Nueva contraseña' : 'Contraseña' }}
                        </label>
                        <input type="password" wire:model="password" autocomplete="new-password"
                               placeholder="{{ $id_almacenista ? 'Déjala vacía para no cambiarla' : 'Mínimo 8 caracteres' }}"
                               class="w-full mb-1 border-gray-300 rounded-md shadow-sm">
                        @error('password') <span class="block mb-2 text-red-500 text-sm">{{ $message }}</span> @enderror
                        @if ($id_almacenista)
                            <p class="mt-1 text-sm text-gray-500">Mínimo 8 caracteres. Si cambias el correo, el almacenista deberá usar el nuevo para iniciar sesión.</p>
                        @endif
                    @else
                        <label class="block text-sm font-medium text-gray-700 mb-1">Correo / Teléfono (opcional)</label>
                        <input type="text" wire:model="email" placeholder="Contacto de referencia" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">

                        <p class="text-sm text-gray-500">
                            Los almacenistas temporales (por ejemplo, de servicio social) no inician sesión; solo aparecen como opción al registrar quién recibe una devolución.
                        </p>
                    @endif
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-3">
                <button wire:click="cerrarModal" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-100 shadow-sm font-medium">
                    Cancelar
                </button>
                <button wire:click="guardar" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 shadow-sm font-medium">
                    Guardar Registro
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
