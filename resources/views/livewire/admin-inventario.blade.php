<div class="max-w-7xl mx-auto p-6 sm:px-6 lg:px-8 mt-8">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Reporte de Inventario</h2>
    </x-slot>

    <div class="bg-white shadow-xl sm:rounded-lg p-6">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 space-y-4 md:space-y-0">
            <input type="text" wire:model.live="search" placeholder="Buscar por nombre o marca..." class="border-gray-300 rounded-md w-full md:w-1/3 shadow-sm">
            
            <div class="flex space-x-3 w-full md:w-auto justify-end">
                <button wire:click="exportarExcel" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 font-bold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Exportar Excel
                </button>
                <button wire:click="exportarPdf" class="bg-red-600 text-white px-4 py-2 rounded shadow hover:bg-red-700 font-bold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Exportar PDF
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase font-bold">Herramienta</th>
                        <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase font-bold">Marca</th>
                        <th class="px-6 py-3 text-center text-xs text-gray-500 uppercase font-bold">Total Físico</th>
                        <th class="px-6 py-3 text-center text-xs text-gray-500 uppercase font-bold">Disponibles</th>
                        <th class="px-6 py-3 text-center text-xs text-gray-500 uppercase font-bold">Prestadas</th>
                        <th class="px-6 py-3 text-center text-xs text-gray-500 uppercase font-bold">Mantenimiento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($inventario as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-700 font-bold">{{ $item->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $item->marca }}</td>
                        
                        <td class="px-6 py-4 text-center text-sm font-bold text-gray-900">
                            {{ $item->total_piezas }}
                        </td>
                        
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                {{ $item->disponibles }}
                            </span>
                        </td>
                        
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->prestadas > 0 ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-500' }}">
                                {{ $item->prestadas }}
                            </span>
                        </td>
                        
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->en_mantenimiento > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-500' }}">
                                {{ $item->en_mantenimiento }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay herramientas en el inventario.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $inventario->links() }}
        </div>
    </div>
</div>