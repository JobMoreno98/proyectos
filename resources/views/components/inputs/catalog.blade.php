@props(['question', 'value'])

@php
    $name = "answers[{$question->id}]";
    $errorKey = "answers.{$question->id}";
    
    // Lógica encapsulada AQUÍ, no en la vista principal
    $catalogName = $question->options['catalog_name'] ?? '';
    $options = \App\Helpers\CatalogProvider::get($catalogName);
    $enableSearch = count($options) > 10;
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required">
    
    <div wire:ignore>
        <select 
            name="{{ $name }}" 
            id="select-{{ $question->id }}"
            class="form-select w-full rounded-md border-gray-300 p-2"
            @if($enableSearch) placeholder="Buscar..." @endif
        >
            <option value="">Seleccione alguna opción...</option>
            @foreach($options as $id => $label)
                <option value="{{ $id }}" {{ ((string)$value === (string)$id) ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    @if($enableSearch)
        <script>
            new TomSelect("#select-{{ $question->id }}", {
                create: false,
                sortField: { field: "text", direction: "asc" }
            });
        </script>
    @endif

</x-inputs.wrapper>