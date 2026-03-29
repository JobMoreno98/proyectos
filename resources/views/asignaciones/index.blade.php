<x-layouts::app :title="$categoria->titulo">
    @push('styles')
        <link href="https://cdn.datatables.net/2.0.2/css/dataTables.tailwindcss.css" rel="stylesheet">
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
            <div wire:ignore>
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
    </div>
    @push('js')
        @include('usuarios.scripts')
        <script>
            function limpiarFantasmasDataTables() {
                if ($.fn.dataTable && $.fn.dataTable.settings) {
                    for (let i = $.fn.dataTable.settings.length - 1; i >= 0; i--) {
                        let config = $.fn.dataTable.settings[i];
                        if (!document.body.contains(config.nTable)) {
                            $.fn.dataTable.settings.splice(i, 1);
                        }
                    }
                }
            }

            function inicializarTablaProyectos() {
                limpiarFantasmasDataTables();
                if (!document.getElementById('proyectos')) return;

                if ($.fn.DataTable.isDataTable('#proyectos')) {
                    try {
                        $('#proyectos').DataTable().destroy();
                    } catch (e) {
                        console.warn("Se ignoró tabla huérfana de DataTables.");
                    }
                }

                $('#proyectos').DataTable({
                    destroy: true,
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
                    columnDefs: [
                        /*
                                                {
                                                    type: 'accent-neutralise',
                                                    targets: [1, 2]
                                                },*/
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
            }

            function esperarDataTables() {
                if (!document.getElementById('proyectos')) return;

                if (window.jQuery && $.fn.DataTable) {
                    inicializarTablaProyectos();
                } else {
                    setTimeout(esperarDataTables, 50);
                }
            }
            document.addEventListener('livewire:navigated', esperarDataTables);
            document.addEventListener('DOMContentLoaded', esperarDataTables);

            document.addEventListener('livewire:navigating', function() {
                if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#proyectos')) {
                    try {
                        $('#proyectos').DataTable().destroy();
                    } catch (e) {}
                }
            });
        </script>
    @endpush
</x-layouts::app>
