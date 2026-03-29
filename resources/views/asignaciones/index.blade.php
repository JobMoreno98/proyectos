<x-layouts::app :title="$categoria->titulo">
    @push('styles')
        <link href="https://cdn.datatables.net/2.0.2/css/dataTables.tailwindcss.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/buttons/3.0.1/css/buttons.tailwindcss.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/searchpanes/2.3.0/css/searchPanes.tailwindcss.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/select/2.0.0/css/select.tailwindcss.css" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @endpush
    <div class="container-fluid m-auto">
        <h2 class="text-center text-stone-900"> {{ $categoria->titulo }}</h2>
        <p class="text-center text-stone-900">
            {{ $categoria->descripcion }}
        </p>
        <div class="p-3 my-4 text-stone-900 rounded-sm border-1 border-b-indigo-500">
            @if ($porAsignar > 0)
                <p class="flex text-md items-center text-center">
                    <flux:icon class="text-red-600 h-4 w-4" name="exclamation-circle" variant="outline" />
                    Aun tienes {{ $porAsignar }} proyectos sin asignar
                </p>

                <x-flux::button size="xs" :href="route('proyectos.show', $categoria->secciones->first()->id)"> Agregar
                    <flux:icon name="plus" variant="micro" />
                </x-flux::button>
            @endif
            <table id="proyectos" style="width: 100%" class="display nowrap">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Nombre</th>
                        <th style="max-width: 15%; overflow-wrap: break-word;">Título</th>
                        <th>Evaluador</th>
                        <th>Evaluacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

        </div>
    </div>
    @push('js')
        @include('usuarios.scripts')
        <script>
            $(document).ready(function() {
                $('#proyectos').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('asignaciones.data') }}',
                    columns: [{
                            data: 'folio',
                            name: 'folio',
                            orderable: false,
                            searchable: true
                        },
                        {
                            data: 'nombre',
                            name: 'nombre',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'titulo',
                            name: 'titulo',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'evaluador',
                            name: 'evaluador',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'evaluacion',
                            name: 'evaluacion',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'acciones',
                            name: 'acciones',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    pageLength: 10,
                    columnDefs: [{
                            type: 'accent-neutralise',
                            targets: [1, 2]
                        },
                        {
                            targets: 2,
                            width: "200px",
                            createdCell: function(td, cellData, rowData, row, col) {
                                $(td).css({
                                    "white-space": "normal",
                                    "word-wrap": "break-word",
                                    "overflow-wrap": "break-word"
                                });
                            }
                        }
                    ],
                    layout: {
                        topStart: [
                            'pageLength',
                            {
                                buttons: [{
                                    text: 'Exportar Completo',
                                    className: 'bg-green-600 text-sm text-white px-4 py-2 rounded shadow hover:bg-green-700 transition',
                                    action: function(e, dt, node, config) {
                                        // 1. dt.ajax.params() extrae toda la configuración actual de la tabla (búsquedas, orden, etc.)
                                        // 2. $.param() convierte eso en formato de URL (?search[value]=Juan&order[0][dir]=asc...)
                                        var params = $.param(dt.ajax.params());
                                        var url = '{{ route('asignaciones.exportar') }}?' +
                                            params;

                                        // 3. Redirigimos a la ruta de exportación pegándole esos parámetros
                                        window.open(url, '_blank');

                                        // 3. Por si el loader que se quedó pegado es el propio de DataTables,
                                        // le mandamos la orden estricta de apagarse.
                                        dt.processing(false);
                                    }
                                }]
                            }
                        ],
                        topEnd: 'search',
                        bottomStart: 'info',
                        bottomEnd: 'paging'
                    },
                    order: [
                        [1, 'asc']
                    ],
                    select: {
                        style: 'api',
                        info: false
                    },
                    responsive: true,
                    language: {
                        url: "https://cdn.datatables.net/plug-ins/2.0.2/i18n/es-MX.json"
                    }
                });
            });
        </script>
    @endpush
</x-layouts::app>
