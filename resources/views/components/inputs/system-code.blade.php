@props(['question', 'value'])

@php
    $userId = auth()->id();
    $userEmailName = auth()->user()->name;

    // --- 1. CONSULTAR DATOS DEL USUARIO ---
    $perfil = \App\Models\ViewDatosGenerales::where('user_id', $userId)->first();
    $nombreCompleto = '';

    if ($perfil && !empty($perfil->datos_json)) {
        // Usamos el código del perfil si existe
        $userId = str_replace('"','',$perfil->datos_json['Código']) ?? $userId;

        $nombres = $perfil->datos_json['Nombres'] ?? '';
        $paterno = $perfil->datos_json['Apellido Paterno'] ?? '';
        $materno = $perfil->datos_json['Apellido Materno'] ?? '';
        $nombreCompleto = str_replace('"','',trim("$nombres $paterno $materno"));
    }

    // Nombre final (Vista SQL > Auth User Name)
    $staticName = $nombreCompleto ?: $userEmailName;
    $initialValue = $value ?? '';

    // --- 2. OBTENER ID DEL ENTRY DE FORMA SEGURA ---
    // A veces la ruta devuelve el objeto Modelo, a veces el ID string.
    $routeEntry = request()->route('entry') ?? request()->route('answer'); // Ajusta según tu nombre de ruta
    $entryId = null;

    if ($routeEntry) {
        $entryId = $routeEntry instanceof \Illuminate\Database\Eloquent\Model ? $routeEntry->id : $routeEntry;
    }
@endphp

{{-- Usamos el Wrapper para que tenga Label, Asterisco rojo y Helper Text --}}
<x-inputs.wrapper :label="$question->label" :required="$question->is_required" :helper-text="$question->helper_text" :name="'answers.' . $question->id">

    <div x-data="systemCodeGenerator({
        initialCode: @js($initialValue),
        userId: @js($userId),
        currentYear: @js(date('Y')),
        staticName: @js($staticName),
        questionId: @js($question->id),
        entryId: @js($entryId),
        urlApi: @js(route('api.validate.folio'))
    })" x-on:recalculate-code.window="generate()" x-init="initComponent()" class="relative">
        {{-- VISUALIZACIÓN DEL CÓDIGO GENERADO --}}
        <div class="flex items-center gap-2 p-3 bg-gray-50 border border-gray-200 rounded-md">

            {{-- Icono de Hash --}}
            <span class="text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                </svg>
            </span>

            {{-- El Código --}}
            <span class="text-lg font-mono font-bold text-gray-700 tracking-wider"
                x-text="code || 'Generando...'"></span>

            {{-- Spinner de carga --}}
            <svg x-show="isLoading" class="animate-spin h-5 w-5 text-blue-500 ml-auto"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>

        {{-- INPUT OCULTO (Lo que se envía al servidor) --}}
        <input type="hidden" name="answers[{{ $question->id }}]" x-model="code">

        {{-- Debug visual (opcional) --}}
        {{-- <p class="text-xs text-gray-400 mt-1">Generado para: {{ $staticName }}</p> --}}
    </div>

</x-inputs.wrapper>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('systemCodeGenerator', (config) => ({
            code: config.initialCode,
            userId: config.userId,
            year: config.currentYear,
            staticName: config.staticName,
            questionId: config.questionId,
            entryId: config.entryId,
            urlApi: config.urlApi,
            isLoading: false,

            initComponent() {
                // Si no hay código guardado, generamos uno nuevo
                if (!this.code) {
                    this.generate();
                }
            },

            async generate() {
                // --- 1. OBTENER VARIABLES DEL DOM ---
                let nameVal = this.staticName;

                // Intentamos buscar inputs dinámicos si existen
                let nameInput = document.querySelector('[data-code-tag="source_name"]');
                if (nameInput && nameInput.value) {
                    nameVal = nameInput.value;
                }

                let typeInput = document.querySelector('[data-code-tag="source_type"]');
                let typeVal = 'X'; // Default
                if (typeInput) {
                    // Soporte para TomSelect o Select normal
                    if (typeInput.tomselect) typeVal = typeInput.tomselect.getValue();
                    else typeVal = typeInput.value;

                    if (!typeVal) typeVal = 'X';
                }

                // --- 2. CALCULAR INICIALES ---
                let initials = 'XX';
                if (nameVal) {
                    // Tomamos primeras letras de cada palabra
                    initials = nameVal.trim()
                        .split(/\s+/)
                        .map(w => w.charAt(0))
                        .join('')
                        .toUpperCase()
                        .substring(0, 4); // Limitamos a 4 letras por si acaso
                }

                // --- 3. CÓDIGO BASE (PRE-VALIDACIÓN) ---
                let baseCode = `${this.userId}-${initials}-${this.year}-${typeVal}`;

                // --- 4. VALIDACIÓN CON SERVIDOR ---
                this.isLoading = true;

                try {
                    // Construcción correcta de URL con parámetros
                    const url = new URL(this
                    .urlApi); // Usamos la URL completa pasada desde Blade

                    url.searchParams.append('code', baseCode);
                    url.searchParams.append('question_id', this.questionId);

                    if (this.entryId) {
                        url.searchParams.append('entry_id', this.entryId);
                    }

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) throw new Error('Error en validación');

                    const data = await response.json();

                    // Asignamos el código final (único)
                    this.code = data.unique_code;

                } catch (error) {
                    console.error('Error generando folio:', error);
                    // Fallback: Usamos el base si falla la API
                    this.code = baseCode;
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
</script>
