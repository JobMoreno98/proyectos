@php
    $titlePage = 'Usuarios';
@endphp


<x-layouts::app :title="$titlePage">
    @push('styles')
        <link href="https://cdn.datatables.net/2.0.2/css/dataTables.tailwindcss.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/buttons/3.0.1/css/buttons.tailwindcss.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/searchpanes/2.3.0/css/searchPanes.tailwindcss.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/select/2.0.0/css/select.tailwindcss.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @endpush
    @if (session('success'))
        <x-alert type="success">
            {{ session('success') }}
        </x-alert>
    @endif

    <div wire:ignore>
        <table id="usuarios" class="w-full text-sm text-left text-gray-500 dark:text-gray-400" style="width:100%">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th>
                        Código
                    </th>
                    <th scope="col" class="px-6 py-3">Nombre</th>
                    <th>División</th>
                    <th>Departamento</th>
                    <th>Proyectos Registrados</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $item)
                    <tr
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td>
                            {{ $item->datos_resueltos['Código'] }}
                        </td>
                        <td class="uppercase">
                            {{ $item->datos_resueltos['Apellido Paterno'] . ' ' . ($item->datos_resueltos['Apellido Materno'] ?? '') . ' ' . $item->datos_resueltos['Nombres'] }}
                        </td>
                        <td class="uppercase">
                            {{ $item->datos_resueltos['División'] }}
                        </td>
                        <td class="uppercase">
                            {{ $item->datos_resueltos['Departamento'] }}
                        </td>
                        <td>
                            {!! $item->count_proyectos !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @push('js')
        @include('usuarios.scripts')

        <script>
            function inicializarTabla() {
                if (typeof $ === 'undefined') {
                    setTimeout(inicializarTabla, 100);
                    return;
                }
                if ($('#usuarios').length === 0) return;

                if ($.fn.DataTable.isDataTable('#usuarios')) {
                    $('#usuarios').DataTable().destroy();
                }

                document.addEventListener('livewire:navigating', function() {
                    let tabla = $('#usuarios');
                    if (tabla.length > 0 && $.fn.DataTable.isDataTable('#usuarios')) {
                        tabla.DataTable().destroy();
                    }
                });
                $('#usuarios').DataTable({
                    pageLength: 10,
                    columnDefs: [{
                        type: 'accent-neutralise',
                        targets: [1, 2]
                    }],

                    order: [
                        [0, "asc"]
                    ],

                    layout: {
                        top1: 'searchPanes',
                        topStart: [
                            'pageLength', 
                            {
                                buttons: [{
                                    extend: 'excelHtml5',
                                    title: 'Usuarios',
                                    className: 'bg-blue-600 text-sm text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition',
                                    exportOptions: {
                                        columns: [0, 1, 2, 4]
                                    }
                                }]
                            }
                        ],
                        topEnd: 'search',
                        bottomStart: 'info',
                        bottomEnd: 'paging' 
                    },
                    searchPanes: {
                        cascadePanes: true,
                        viewTotal: true,
                        columns: [2, 3],
                    },

                    select: {
                        style: 'api',
                        info: false
                    },
                    responsive: true,
                    language: {
                        url: "https://cdn.datatables.net/plug-ins/2.0.2/i18n/es-MX.json"
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', inicializarTabla); // Si el usuario recarga la página (F5)
            document.addEventListener('livewire:navigated', inicializarTabla); // Si el usuario llega navegando por Livewire
        </script>
    @endpush


</x-layouts::app>
