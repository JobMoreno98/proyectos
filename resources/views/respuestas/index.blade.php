@php
    $titlePage = 'Mis proyectos';
@endphp
<x-layouts::app :title="$titlePage">
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 ">
            <div class="bg-white p-2 shadow-xl sm:rounded-lg">
                <div class="flex justify-end ">
                    <a href="{{ route('proyectos.create', $idProyectos) }}"
                        class="text-indigo-600 hover:text-indigo-900 text-sm font-large text-lg flex space-x-2">
                        <flux:icon.plus variant="mini" />
                        Crear proyecto
                    </a>
                </div>

                @foreach ($proyectos as $item)
                    <div
                        class="m-3 p-2 grid grid-cols-[auto_4rem] justify-stretch border border-stone-300  items-center rounded-lg">
                        <div class=" p-2 mb-2  text-wrap ">
                            <b> Folio: </b>{{ $item->respuesta }} <br>

                            <b> Título:</b> {{ $item->titulo->respuesta }} <br>

                            <b>Fecha:</b> {{ $item->titulo->fecha_creado->format('d/m/y') }}
                        </div>
                        <div class="flex space-x-3">
                            {{-- 
                            <x-a href="{{ route('proyectos.edit', $item->answer_id) }}"> Editar </x-a>
                             --}}
                            @if ($item->is_editable)
                                <a href="{{ route('proyectos.edit', $item->entry_id) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    <flux:icon.pencil variant="mini" />
                                </a>


                                {{-- Botón Eliminar --}}
                                <form action="{{ route('proyectos.destroy', $item->entry_id) }}" method="POST"
                                    onsubmit="return confirm('¿Eliminar este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        <flux:icon.trash variant="mini" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts::app>
