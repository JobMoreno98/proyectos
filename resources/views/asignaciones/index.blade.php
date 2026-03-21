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
                        <th style="max-width: 15%;    overflow-wrap: break-word;">Título</th>
                        <th>Evaluador</th>
                        <th>Evaluacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($datos as $key => $value)
                        <tr>
                            <td> {{ isset($value->data['folio']) ? $value->data['folio'] : '' }} <br>

                            </td>
                            <td>
                                {{ $value->user->name }}
                            </td>
                            <td style="max-width: 15%; overflow-wrap: break-word;">
                                {{ isset($value->data['titulo']) ? $value->data['titulo'] : '' }}
                            </td>
                            <td> {{ isset($value->evalaudor_data['nombres']) ? $value->evalaudor_data['nombres'] . ' ' . $value->evalaudor_data['apellido-paterno'] : 'Sin evaluador' }}
                            </td>
                            <td>Trabajando</td>
                            <td>
                                <div class="flex space-x-3">
                                    {{-- Documento --}}

                                    <a href="{{ route('infor.form', $value->entry_id) }}" target="_blank"
                                        rel="noopener noreferrer">
                                        <flux:icon.document variant="solid" />
                                    </a>
                                    {{-- Botones según asignación --}}

                                    @isset($value->asignacion)
                                        <a href="{{ route('proyectos.edit', $value->asignacion) }}"
                                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            <flux:icon.user-plus variant="solid" />
                                        </a>

                                        <form action="{{ route('proyectos.destroy', $value->asignacion) }}" method="POST"
                                            onsubmit="return confirm('¿Eliminar este registro?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                <flux:icon.trash variant="solid" />
                                            </button>
                                        </form>
                                    @else
                                        @if ($value->is_editable)
                                            <span class="text-gray-500 text-sm">No definitivo</span>
                                        @else
                                            <a href="{{ route('proyectos.send', $value->entry_id) }}"
                                                class="flex items-center gap-1 text-green-600 hover:text-yellow-900 text-sm font-medium">
                                                <flux:icon.arrow-turn-right-up variant="mini" />
                                                Regresar
                                            </a>
                                        @endif
                                        <form action="{{ route('proyectos.destroy', $value->entry_id) }}" method="POST"
                                            onsubmit="return confirm('¿Eliminar este registro?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                <flux:icon.trash variant="mini" />
                                            </button>
                                        </form>
                                    @endisset
                                </div>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
    @push('js')
        @include('usuarios.scripts')
        <script>
            $('#proyectos').DataTable({
                pageLength: 10,
                columnDefs: [{
                        type: 'accent-neutralise',
                        targets: [1, 2]
                    },
                    {
                        targets: 2, // índice de la columna (0 = primera)
                        width: "200px", // ancho fijo
                        createdCell: function(td, cellData, rowData, row, col) {
                            $(td).css({
                                "white-space": "normal",
                                "word-wrap": "break-word",
                                "overflow-wrap": "break-word"
                            });
                        }
                    }
                ],
                order: [
                    [0, "asc"]
                ],
                layout: {
                    topStart: [
                        'pageLength',
                        {
                            buttons: [{
                                extend: 'excelHtml5',
                                title: 'Asignacion Proyectos',
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
                select: {
                    style: 'api',
                    info: false
                },
                responsive: true,
                language: {
                    url: "https://cdn.datatables.net/plug-ins/2.0.2/i18n/es-MX.json"
                }
            });
        </script>
    @endpush
</x-layouts::app>
