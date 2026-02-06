<x-layouts::app :title="$categoria->titulo">
    <div class="container m-auto">
        <h2 class="text-center text-stone-900"> {{ $categoria->titulo }}</h2>
        <p class="text-center text-stone-900">
            {{ $categoria->descripcion }}
        </p>
        <div class="p-3 my-4 text-stone-900 rounded-sm border-1 border-b-indigo-500">
            @if ($porAsignar > 0)
                <p class="flex">
                    <flux:icon class="text-red-600" name="exclamation-circle" variant="outline" />
                    Aun tienes {{ $porAsignar }} proyectos sin asignar
                </p>

                <x-flux::button size="xs" :href="route('proyectos.show', $categoria->secciones->first()->id)"> Agregar
                    <flux:icon name="plus" variant="micro" />
                </x-flux::button>
            @endif

            @forelse ($datos as $key => $value)
                <li
                    class="py-3 flex border my-1 b-3 rounded-lg boder-stone-300 justify-between items-center hover:bg-gray-50 transition p-2 rounded">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700">
                            {{ isset($value->data['titulo']) ? $value->data['titulo'] : '' }} <br>
                            {{ $value->fecha_creado->format('d/m/Y') }} <br>
                            Evaluador:
                            {{ isset($value->evalaudor_data['nombres']) ? $value->evalaudor_data['nombres'] . ' ' . $value->evalaudor_data['apellido-paterno'] : 'Sin evaluador' }}

                        </span>
                    </div>
                    <div class="flex space-x-3">
                        <div class="flex space-x-3">

                            <a href="{{ route('infor.form', $value->entry_id) }}"
                                class="text-black-600 hover:text-indigo-900 text-sm font-medium">
                                <flux:icon.document variant="solid" />
                            </a>

                            {{-- Botones Activos --}}
                            {{-- Botón Editar --}}
                            @if ($value->asignacion)
                                <a href="{{ route('proyectos.edit', $value->asignacion) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    <flux:icon.user-plus variant="solid" />
                                </a>



                                {{-- Botón Eliminar --}}
                                <form action="{{ route('proyectos.destroy', $value->asignacion) }}" method="POST"
                                    onsubmit="return confirm('¿Eliminar este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        <flux:icon.trash variant="solid" />
                                    </button>
                                </form>
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
