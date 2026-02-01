{{-- 1. PROPS IDÉNTICAS A TUS OTROS INPUTS --}}
@props(['question', 'value', 'name' => null])

@php
    // --- LÓGICA DE NOMBRE FLEXIBLE (Igual que tu Text/Textarea) ---
    $inputName = $name ?? "answers[{$question->id}]";
    $errorKey = str_replace(['[', ']'], ['.', ''], $inputName); // answers.15

    // --- LÓGICA DE DATOS (ESPECÍFICA PARA REPEATER) ---
    // Prioridad: 1. Old Inputs (si falló validación) -> 2. Valor BD -> 3. Valor por defecto
    $data = old($errorKey, $value);

    // Si viene de BD como string JSON, lo decodificamos
    if (is_string($data)) {
        $data = json_decode($data, true);
    }

    // Si está vacío o no es array, iniciamos con una fila vacía
    if (empty($data) || !is_array($data)) {
        $data = [['id' => time(), 'nombre' => '', 'tipo' => '']];
    }
    $choices = $question->options['choices'] ?? [];
@endphp

{{-- 2. USAMOS TU WRAPPER EXISTENTE --}}
<x-inputs.wrapper :label="$question->label" :name="$errorKey" :required="$question->is_required" :helper-text="$question->helper_text">


    {{-- 3. EL COMPONENTE ALPINE --}}
    <div x-data="{
        items: {{ Js::from($data) }}, // Inyectamos los datos procesados arriba
    
        addItem() {
            this.items.push({
                id: Date.now(), // ID único para evitar bugs de renderizado
                nombre: '',
                tipo: ''
            });
        },
    
        removeItem(index) {
            this.items.splice(index, 1);
        }
    }" class="space-y-3">

        {{-- Bucle de filas --}}
        <template x-for="(item, index) in items" :key="item.id || index">
            <div class="flex gap-3 items-start p-3 bg-gray-50">

                {{-- CAMPO 1: NOMBRE --}}
                <div class="flex-grow">
                    <label class="text-xs text-gray-500 mb-1 block">Nombre</label>
                    <input type="text" x-model="item.nombre" :name="`{{ $inputName }}[${index}][nombre]`" 
                        class="p-2 form-input w-full rounded-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 border-gray-300"
                        placeholder="Nombre">
                </div>

                <div class="w-1/3">
                    <label class="text-xs text-gray-500 mb-1 block">Tipo</label>

                    <select x-model="item.tipo" :name="`{{ $inputName }}[${index}][tipo]`"
                        class="form-select text-stone-900 border-gray-300 rounded-xs shadow-md focus:border-blue-500 focus:ring focus:ring-blue-200 w-full p-2">

                        <option value="">Seleccionar...</option>

                        {{-- ITERAMOS LAS OPCIONES DINÁMICAS --}}
                        @foreach ($choices as $choice)
                            <option value="{{ $choice['value'] }}">
                                {{ $choice['label'] }}
                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- BOTÓN ELIMINAR --}}
                <div class="pt-5"> {{-- Padding top para alinear con los inputs --}}
                    <button type="button" @click="removeItem(index)"
                        class="text-red-400 hover:text-red-600 transition p-1" title="Eliminar fila">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        {{-- BOTÓN AGREGAR --}}
        <button type="button" @click="addItem()"
            class="flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Agregar otro registro
        </button>

    </div>

</x-inputs.wrapper>
