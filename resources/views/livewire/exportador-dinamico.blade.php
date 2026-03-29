<div class="max-w-7xl mx-auto py-10 px-4">

    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
        </div>

        <button wire:click="procesarExportacion"
            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow flex items-center gap-2 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Generar Excel ({{ count($preguntasSeleccionadas ?? []) }})
        </button>
        <div class="flex justify-end gap-3 mb-4">
            <button wire:click="seleccionarTodo"
                class="text-sm bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold py-1.5 px-4 rounded-md border border-blue-200 transition shadow-sm flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Marcar todo
            </button>

            <button wire:click="deseleccionarTodo"
                class="text-sm bg-gray-50 hover:bg-gray-100 text-gray-600 font-semibold py-1.5 px-4 rounded-md border border-gray-200 transition shadow-sm flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Limpiar selección
            </button>
        </div>
    </div>

    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white grid col-span-1 md:col-span-3 rounded-xl shadow-md border border-gray-200 overflow-hidden">

        <div class="p-6 md:p-10 space-y-12">

            @foreach ($secciones as $seccion)
                <div class="relative">
                    <h3
                        class="text-lg font-bold text-blue-800 border-b-2 border-blue-100 pb-2 mb-5 uppercase tracking-wide">
                        {{ $seccion->title }}
                    </h3>
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

                        @foreach ($seccion->questions as $pregunta)
                            @if ($pregunta->type === 'sub_form')
                                {{-- ES UN SUB-FORMULARIO --}}
                                @php
                                    $targetId = $pregunta->options['target_section_id'] ?? null;
                                    $subSeccion = $targetId ? $subSecciones[$targetId] ?? null : null;
                                @endphp

                                @if ($subSeccion)
                                    <div class="col-span-1 p-3 bg-blue-50/50 border border-blue-100 rounded-lg">
                                        <p
                                            class="text-xs font-bold text-blue-800 uppercase mb-2 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                            </svg>
                                            {{ $pregunta->label }}
                                        </p>

                                        <div class="space-y-2 pl-2 border-l-2 border-blue-200">
                                            @foreach ($subSeccion->questions as $subQ)
                                                <label
                                                    class="flex items-start space-x-3 cursor-pointer hover:bg-white p-1 rounded transition">
                                                    <input type="checkbox" wire:model.live="preguntasSeleccionadas"
                                                        value="{{ $subQ->id }}"
                                                        class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                    <span class="text-sm text-gray-700 leading-tight">
                                                        {{ $subQ->label }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @else
                                {{-- ES UNA PREGUNTA NORMAL --}}
                                <label
                                    class="flex items-start space-x-3 cursor-pointer hover:bg-gray-50 p-3 rounded-lg transition border border-gray-100 hover:border-gray-300 shadow-sm">
                                    <input type="checkbox" wire:model.live="preguntasSeleccionadas"
                                        value="{{ $pregunta->id }}"
                                        class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-sm text-gray-700 font-medium leading-tight">
                                        {{ $pregunta->label }}
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>

                </div>
            @endforeach

        </div>
    </div>
</div>
