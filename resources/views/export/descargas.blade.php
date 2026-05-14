<x-layouts::app :title="__('Dashboard')">
    @if (session('success'))
        <x-alert type="success">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="grid place-items-center min-h-[80vh] w-full px-4">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl uppercase font-semibold text-gray-800">
                    descargas
                </h2>
            </div>

            <div class="overflow-x-auto bg-white shadow rounded-lg">

                <table class="min-w-full divide-y divide-gray-200">

                    <!-- HEADER -->
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Archivo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acción
                            </th>
                        </tr>
                    </thead>

                    <!-- BODY -->
                    <tbody class="bg-white divide-y divide-gray-200">

                        @foreach ($downloads as $download)
                            <tr>

                                <!-- ARCHIVO -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $download->file_name }}
                                </td>

                                <!-- ESTADO -->
                                <td class="px-6 py-4 whitespace-nowrap">

                                    @if ($download->status === 'completed')
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Completado
                                        </span>
                                    @elseif($download->status === 'processing')
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Procesando
                                        </span>
                                    @elseif($download->status === 'pending')
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            En cola
                                        </span>
                                    @else
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Error
                                        </span>
                                    @endif

                                </td>

                                <!-- FECHA -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $download->created_at->format('d/m/Y H:i') }}
                                </td>

                                <!-- ACCIÓN -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                                    @if ($download->status === 'completed')
                                        <a href="{{ route('downloads.file', $download->id) }}"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            Descargar
                                        </a>
                                    @elseif($download->status === 'processing')
                                        <span class="text-gray-400">
                                            Generando...
                                        </span>
                                    @elseif($download->status === 'pending')
                                        <span class="text-gray-400">
                                            En espera
                                        </span>
                                    @else
                                        <span class="text-red-500">
                                            Falló
                                        </span>
                                    @endif

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>
        </div>
    </div>
</x-layouts::app>
