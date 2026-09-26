<div class="max-w-7xl mx-auto p-6 sm:px-6 lg:px-8 mt-8">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Directorio de Usuarios</h2>
    </x-slot>

    <div class="bg-white shadow-xl sm:rounded-lg p-6">
        <div class="flex justify-between mb-6">
            <input type="text" wire:model.live="search" placeholder="Buscar por nombre o control..." 
                   class="border-gray-300 rounded-md w-1/3 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            <button wire:click="abrirModal" class="bg-indigo-600 text-white px-4 py-2 rounded-md shadow hover:bg-indigo-700 font-bold">
                + Nuevo Usuario
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Control</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Depto/Carrera</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($usuarios as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->numero_control }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $user->nombre_completo }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->tipo_usuario }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->carrera_departamento ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $user->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($user->estado) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium space-x-3">
                            <button wire:click="editar({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                            <button wire:click="borrar({{ $user->id }})" wire:confirm="¿Estás seguro de que deseas eliminar a este usuario? Esta acción no se puede deshacer."class="text-red-600 hover:text-red-900 font-bold">Borrar</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No se encontraron usuarios.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $usuarios->links() }}
        </div>
    </div>

    @if($modalAbierto)
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-bold text-gray-800">{{ $id_usuario ? 'Editar Registro de Usuario' : 'Registrar Nuevo Usuario' }}</h3>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Control</label>
                    <input type="text" wire:model="numero_control" placeholder="Escribe 'NO HAY' si es trabajador" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">

                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                    <input type="text" wire:model="nombre_completo" placeholder="Ej. Juan Pérez" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">

                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Usuario</label>
                    <select wire:model="tipo_usuario" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">
                        <option value="Alumno">Alumno</option>
                        <option value="Docente">Docente</option>
                        <option value="Administrativo">Administrativo</option>
                        <option value="Trabajador">Trabajador</option>
                    </select>

                    <label class="block text-sm font-medium text-gray-700 mb-1">Carrera / Departamento</label>
                    <input type="text" wire:model="carrera_departamento" placeholder="Ej. Sistemas / Mantenimiento" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <div class="flex space-x-2 mb-3">
                        <div class="w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                            <input type="text" wire:model="semestre" placeholder="Ej. 6" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Grupo</label>
                            <input type="text" wire:model="grupo" placeholder="Ej. A" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>

                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" wire:model="telefono" placeholder="10 dígitos" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">

                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado en el Sistema</label>
                    <select wire:model="estado" class="w-full mb-3 border-gray-300 rounded-md shadow-sm">
                        <option value="activo">Activo (Puede pedir préstamos)</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
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