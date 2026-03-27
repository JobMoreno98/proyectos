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
                            Título: {{ isset($value->asignado['titulo']) ? $value->asignado['titulo'] : '' }}<br>
                            Folio: {{ isset($value->asignado['folio']) ? $value->asignado['folio'] : '' }}<br>
                            {{ $value->info->fecha_creado->format('d/m/Y') }}
                        </span>
                    </div>
                    <div class="flex space-x-3">
                        <div class="flex space-x-3">

                            <a href="{{ route('infor.form', $value->info->respuesta) }}"
                                class="text-black-600 hover:text-indigo-900 text-sm font-medium">
                                <flux:icon.information-circle variant="mini" />
                            </a>
                        
                            {{ $value->evaluacion }}
                            @if (isset($value->evaluacion))
                                <a target="_blank" href="{{ route('proyectos.print', $value->evaluacion->entry_id) }}"
                                    class="text-black-600 hover:text-indigo-900 text-sm font-medium">
                                    <flux:icon.printer variant="mini" />
                                </a>
                                @if ($value->evaluacion->is_editable)
                                    <a href="{{ route('proyectos.send', $value->evaluacion->entry_id) }}"
                                        class="flex items-center gap-1 text-green-600 hover:text-yellow-900 text-sm font-medium">
                                        <flux:icon.check-circle variant="mini" />
                                        Enviar
                                    </a>
                                    <a href="{{ route('evaluacion.edit', $value->evaluacion->entry_id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        <flux:icon.pencil variant="mini" />
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('evaluacion.create', $value->info->respuesta) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    <flux:icon.pencil variant="mini" />
                                </a>
                            @endif



                            {{-- 
                                    Botón Eliminar
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

                        </div>
                    </div>
                </li>

            @empty
                <p class="text-gray-400 italic text-sm text-center py-4">
                    No se ha registrado información en esta sección aún.
                </p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
