@props(['question', 'value', 'type' => 'text', 'name' => null])

@php
    $name = $name ?? "answers[{$question->id}]";
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<x-inputs.wrapper class="" :label="$question->label" :name="$errorKey" :required="$question->is_required"
    :helper-text="$question->helper_text">

    {{-- Contenedor Alpine para manejar la lógica del editor --}}
    <div x-data="{
        content: @js($value),
        initQuill() {
            const quill = new Quill(this.$refs.editor, {
                theme: 'snow',
                placeholder: 'Escribe tu respuesta aquí...',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });
    
            // Cargar valor inicial si existe
            if (this.content) {
                quill.root.innerHTML = this.content;
            }
    
            // Sincronizar cambios del editor hacia la variable 'content'
            quill.on('text-change', () => {
                this.content = quill.root.innerHTML;
            });
        }
    }" x-init="initQuill()" class="mt-1" wire:ignore >

        <div x-ref="editor" class="w-full  bg-white rounded-t-none !rounded-b-md" style="min-height: 100px;"></div>

        {{-- Input oculto real que se enviará en el formulario --}}
        <textarea name="{{ $name }}" id="{{ $errorKey }}" class="hidden" x-model="content"></textarea>
    </div>

    {{-- Estilos inline para arreglar bordes con Tailwind (Opcional, mejor mover a tu CSS) --}}
    <style>
        .ql-toolbar.ql-snow {
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            border-color: #d1d5db;
            /* Gray-300 de Tailwind */
        }

        .ql-container.ql-snow {
            border-bottom-left-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
            border-color: #d1d5db;
        }

        .ql-editor {
            min-height: 150px;
            max-height: 250px;
            overflow-y: auto;
        }
    </style>

</x-inputs.wrapper>
