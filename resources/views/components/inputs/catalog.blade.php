@props(['question', 'value', 'name' => null])

@php
    $inputName = $name ?? "answers[{$question->id}]";
    $errorKey = str_replace(['[', ']'], ['.', ''], $inputName);

    // Lógica encapsulada AQUÍ, no en la vista principal
    $catalogName = $question->options['catalog_name'] ?? '';
    $options = \App\Helpers\CatalogProvider::get($catalogName);
    $enableSearch = count($options) > 10;
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required" :helper-text="$question->helper_text">

    <div wire:ignore>
        <select name="{{ $inputName }}" id="select-{{ $question->id }}"
            class="form-select  text-center border border-stone-300 p-2 text-stone-900 border-gray-300 rounded-md shadow-sm focus:border-blue-500 
        focus:ring focus:ring-blue-200 w-full"
            @if ($enableSearch) placeholder="Buscar..." @endif>
            <option value="">Seleccione alguna opción...</option>
            @foreach ($options as $id => $label)
                <option value="{{ $id }}" {{ (string) $value === (string) $id ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    @if ($enableSearch)
        <script>
            new TomSelect("#select-{{ $question->id }}", {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        </script>
    @endif

</x-inputs.wrapper>
