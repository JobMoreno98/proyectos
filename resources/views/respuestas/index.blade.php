<x-layouts::app>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white  shadow-xl sm:rounded-lg p-3">
                @foreach ($proyectos as $item)
                    <div class="max-w-6xl m-3 p-2 flex justify-between items-center border border-stone-400 rounded-lg mb-2">
                        <div>
                            {{ $item->respuesta }} <br>
                            {{ $item->titulo->respuesta }} <br>
                            {{ $item->titulo->fecha_creado->format('d/m/Y') }}
                        </div>
                        <div>
                            <div class="flex space-x-3">

                                <a href="{{ route('proyectos.edit', $item->entry_id) }}"
                                    class="text-black-600 hover:text-indigo-900 text-sm font-medium">
                                    <flux:icon.printer variant="mini" />
                                </a>

                                <a href="">
                                <flux:icon.check-circle variant="mini"/>
                                </a>

                                @if ($item->is_editable)
                                    {{-- Botones Activos --}}
                                    {{-- Botón Editar --}}
                                    <a href="{{ route('proyectos.edit', $item->entry_id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        <flux:icon.pencil variant="mini" />
                                    </a>


                                    {{-- Botón Eliminar --}}
                                    <form action="{{ route('proyectos.destroy', $item->entry_id) }}" method="POST"
                                        onsubmit="return confirm('¿Eliminar este registro?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-900 text-sm font-medium">
                                            <flux:icon.trash variant="mini" />
                                        </button>
                                    </form>
                                @endif
                            </div>


                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts::app>
