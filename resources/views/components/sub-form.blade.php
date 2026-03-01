@props(['item'])

@php
    $section = $item['subForm']['section'] ?? null;
    $answers = $item['subForm']['answers'] ?? [];
@endphp

@if ($section)
    <div
        class="col-span-2 space-y-5 mt-4  p-2 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50">

        <h4 class="col-span-1 md:col-span-2 text-blue-800 font-bold mb-3 border-b-2 border-blue-500 pb-2">
            {{ $section->title }}
        </h4>

        @foreach ($section->questions as $childQ)
            @php
                // 1. OBTENEMOS LAS VARIABLES DE DEPENDENCIA DEL HIJO
                $isChildDependent = filter_var($childQ->options['is_dependent'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $childParentId = $childQ->options['depends_on_question_id'] ?? null;
                $childExpectedValue = $childQ->options['depends_on_value'] ?? null;

                // 2. CREAMOS LOS NOMBRES DE LOS INPUTS
                // El nombre de ESTE input (el que se está dibujando)
                $childInputName = "sub_answers[{$item['model']->id}][{$childQ->id}]";

                // El nombre del input PADRE (el que va a escuchar Alpine)
                $childParentNameString = "sub_answers[{$item['model']->id}][{$childParentId}]";

                // 3. RECUPERAMOS EL VALOR GUARDADO/VIEJO
                $childValue = old(
                    "sub_answers.{$item['model']->id}.{$childQ->id}",
                    $answers[$childQ->id] ?? ($childQ->options['default_value'] ?? ''),
                );

                // 4. DETERMINAMOS EL COMPONENTE
                $childComponent = 'inputs.' . $childQ->type;

                if (!view()->exists("components.{$childComponent}")) {
                    $childComponent = 'inputs.text';
                }
            @endphp

            {{-- 5. APLICAMOS LA MAGIA DE ALPINE EN EL DIV ENVOLTORIO --}}
            <div @if ($isChildDependent && $childParentId) @php
            $childExpectedStr = trim($childExpectedValue);
        @endphp
        
        {{-- Llamamos a la lógica separada --}}
        x-data="formDependency('{{ $childParentNameString }}', @js($childExpectedStr))"
        x-show="show"
x-collapse.duration.300ms
        x-cloak @endif
                class="mb-3 w-full">
                <x-dynamic-component :component="$childComponent" :question="$childQ" :value="$childValue" :name="$childInputName" />
            </div>
        @endforeach
    </div>
@endif
