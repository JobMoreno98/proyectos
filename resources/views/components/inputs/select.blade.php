@props(['question', 'value', 'type' => 'select', 'name' => null])

@php
    $inputName = $name ?? "answers[{$question->id}]";
    $errorKey = str_replace(['[', ']'], ['.', ''], $inputName);
    
    // Decidir si usamos TomSelect (>10 opciones)
    $enableSearch = !empty($question->options['choices']) && count($question->options['choices']) > 10;
    
    $codeTag = $question->options['code_tag'] ?? null;
@endphp

<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required" :helper-text="$question->helper_text">
    
    <div @if($enableSearch) wire:ignore @endif>
        <select 
            name="{{ $inputName }}" 
            id="select-{{ $question->id }}"
            
            {{-- 1. ETIQUETA DE RASTREO --}}
            @if($codeTag) data-code-tag="{{ $codeTag }}" @endif
            
            {{-- 
                 2. CORRECCIÓN CRÍTICA: JavaScript Nativo
                 Usamos 'onchange' estándar en lugar de 'x-on:change'.
                 Esto garantiza que el evento se dispare siempre, sin depender de Alpine.
            --}}
            onchange="window.dispatchEvent(new CustomEvent('recalculate-code'))"

            @if($enableSearch) placeholder="Buscar..." @endif
            
            class="form-select text-stone-900 border-gray-300 rounded-xs shadow-md focus:border-blue-500 focus:ring focus:ring-blue-200 w-full p-2"
        >
            <option value="">Seleccione...</option>

            @if(!empty($question->options['choices']))
                @foreach($question->options['choices'] as $choice)
                    @php 
                        $cVal = is_array($choice) ? $choice['value'] : $choice;
                        $cLab = is_array($choice) ? $choice['label'] : $choice;
                    @endphp
                    
                    <option value="{{ $cVal }}" 
                        {{ (string)old($errorKey, $value) === (string)$cVal ? 'selected' : '' }}>
                        {{ $cLab }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    {{-- 3. SCRIPT PARA TOMSELECT (Solo si hay muchas opciones) --}}
    @if($enableSearch)
        <script>
            (function() {
                var selectElement = document.getElementById('select-{{ $question->id }}');
                var ts = new TomSelect("#select-{{ $question->id }}", {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    onChange: function(value) {
                        // Forzamos la actualización visual y de datos
                        selectElement.value = value;
                        selectElement.setAttribute('value', value);
                        // Disparamos el mismo evento que el nativo
                        window.dispatchEvent(new CustomEvent('recalculate-code'));
                    }
                });
                // Conectamos la instancia para que system-code la lea si es necesario
                selectElement.tomselect = ts;
            })();
        </script>
    @endif

</x-inputs.wrapper>