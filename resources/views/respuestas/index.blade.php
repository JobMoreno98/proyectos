<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Proyectos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white  shadow-xl sm:rounded-lg">
                @foreach ($proyectos as $item)
                    <div class="max-w-7xl m-3 p-2 flex justify-between ">
                        {{ dd($item) }}
                        <div>
                            {{ $item->respuesta }}
                        </div>
                        <div>
                            {{-- 
                            <x-a href="{{ route('proyectos.edit', $item->answer_id) }}"> Editar </x-a>
                             --}}
                            @if ($item->is_editable)
                                {{-- Botones Activos --}}
                                {{-- Botón Editar --}}
                                <a href="{{ route('proyectos.edit', $item->entry_id) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Editar
                                </a>


                                {{-- Botón Eliminar --}}
                                <form action="{{ route('proyectos.destroy', $item->entry_id) }}" method="POST"
                                    onsubmit="return confirm('¿Eliminar este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </div>



                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
