<x-layouts::app :title="$categoria->titulo">
    <div class="container m-auto">
        <h2 class="text-center text-stone-900"> {{ $categoria->titulo }}</h2>
        <p class="text-center text-stone-900">
            {{ $categoria->descripcion }}
        </p>
        <div class="p-3 my-4 text-stone-900 rounded-sm border-1 border-b-indigo-500">
            @forelse ($datos as $key => $value)
                <li
                    class="py-3 flex border b-3 rounded-lg boder-stone-300 justify-between items-center hover:bg-gray-50 transition p-2 rounded">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700">
                           
                            {{ isset($value->info->proyecto['titulo']) ? $value->info->proyecto['titulo'] : '' }} <br>
                            {{ $value->info->fecha_creado->format('d/m/Y') }}
                        </span>
                    </div>


                    <div class="flex space-x-3">
                        <div class="flex space-x-3">

                            <a href="{{ route('infor.form', $value->info->respuesta) }}"
                                class="text-black-600 hover:text-indigo-900 text-sm font-medium">
                                <flux:icon.document variant="solid" />
                            </a>
                            @if ($value->is_editable)
                                {{-- Botones Activos --}}
                                {{-- Botón Editar --}}
                                <a href="{{ route('evaluacion.edit', $value->info->respuesta) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    <flux:icon.pencil variant="solid" />
                                </a>


                                {{-- Botón Eliminar
                                    <form action="{{ route('proyectos.destroy', $value->entry_id) }}" method="POST"
                                        onsubmit="return confirm('¿Eliminar este registro?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-900 text-sm font-medium">
                                            <flux:icon.trash variant="mini" />
                                        </button>
                                    </form>
                                     --}}
                            @endif
                        </div>
                    </div>
                </li>

            @empty
                <p class="text-gray-400 italic text-sm text-center py-4">
                    No has registrado información en esta sección aún.
                </p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
