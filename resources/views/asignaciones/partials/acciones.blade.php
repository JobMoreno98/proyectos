<div class="flex items-center space-x-3">
    {{-- Documento --}}
    <a href="{{ route('infor.form', $value->entry_id) }}" target="_blank" rel="noopener noreferrer"
        class="text-gray-500 hover:text-gray-700">
        <flux:icon.document variant="mini" />
    </a>


    @isset($value->asignacion)
        <a href="{{ route('proyectos.edit', $value->asignacion) }}"
            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
            <flux:icon.user-plus variant="mini" />
        </a>


        @if (isset($value->id_evaluacion))
            <a href="{{ route('proyectos.send', $value->id_evaluacion) }}"
                class="flex items-center gap-1 text-green-600 hover:text-yellow-600 text-sm font-medium transition-colors">
                <flux:icon.arrow-turn-right-up variant="mini" />

            </a>
            <a href="{{ route('evaluacion.print', $value->id_evaluacion) }}" target="_blank" rel="noopener noreferrer"
                class="text-gray-500 hover:text-gray-700">
                <flux:icon.document-text variant="mini" />
            </a>
        @else
            <form action="{{ route('proyectos.destroy', $value->asignacion) }}" method="POST"
                onsubmit="return confirm('¿Eliminar este registro?');" class="inline-block m-0 p-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium transition-colors">
                    <flux:icon.trash variant="mini" />
                </button>
            </form>
        @endif
    @else
        <a href="{{ route('asginar.proyecto', $value->entry_id) }}"
            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
            <flux:icon.user-plus variant="mini" />
        </a>

        <form action="{{ route('proyectos.destroy', $value->entry_id) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este registro?');" class="inline-block m-0 p-0">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="flex items-center gap-1 text-red-600 hover:text-green-600 text-sm font-medium transition-colors">
                <flux:icon.trash variant="mini" />
            </button>
        </form>


        @if ($value->is_editable)
            <a href="{{ route('proyectos.send', $value->entry_id) }}" class="">
                <flux:icon.check-circle variant="mini" />
            </a>
        @else
            <a href="{{ route('proyectos.send', $value->entry_id) }}"
                class="flex items-center gap-1 text-green-600 hover:text-yellow-600 text-sm font-medium transition-colors">
                <flux:icon.arrow-turn-right-up variant="mini" />

            </a>
        @endif
    @endisset
</div>
