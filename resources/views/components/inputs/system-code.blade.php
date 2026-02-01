@props(['question', 'value'])

@php
    $userId = auth()->id();
    $userEmailName = auth()->user()->name;

    // --- 1. CONSULTAR LA VISTA SQL ---
    // Buscamos si este usuario ya tiene datos generales registrados
    $perfil = \App\Models\ViewDatosGenerales::find($userId);

    $nombre = '';

    if ($perfil && !empty($perfil->datos_json)) {
        $userId = $perfil->datos_json['Código'];
        // Obtenemos el nombre del JSON (Asegúrate que la key coincida con tu BD)
        $nombreParaCodigo = $perfil->datos_json['Nombres'] ?? ($perfil->datos_json['Nombres'] ?? null);
        $apellidoPaterno = $perfil->datos_json['Apellido Paterno'] ?? ($perfil->datos_json['Apellido Paterno'] ?? null);
        $apellidoMaterno = $perfil->datos_json['Apellido Materno'] ?? ($perfil->datos_json['Apellido Materno'] ?? null);
        $nombre = $nombreParaCodigo . ' ' . $apellidoPaterno . ' ' . $apellidoMaterno;
    }
    // --- 2. DEFINIR EL NOMBRE FINAL ---
    // Prioridad: Vista SQL > Nombre del Usuario Logueado
    $staticName = $nombre ?: $userEmailName;

    // Escapamos comillas simples por si alguien se llama "D'Artagnan" para no romper el JS
    $staticNameSafe = addslashes($staticName);

    $initialValue = $value ?? '';
@endphp

<div {{-- Pasamos el nombre estático a la configuración de JS --}} x-data="systemCodeGenerator({
    initialCode: '{{ $initialValue }}',
    userId: '{{ $userId }}',
    currentYear: '{{ date('Y') }}',
    staticName: '{!! $staticNameSafe !!}'
    {{-- Usamos {!! !!} porque ya aplicamos addslashes --}}
})" x-on:recalculate-code.window="generate()" x-init="initComponent()"
    class="" :helper-text="$question->helper_text">
    <h4 class="text-sm font-bold  uppercase mb-2 flex items-center gap-2">
        {{ $question->label }} <span class="text-red-400" x-text="code"></span>
    </h4>

    <input type="hidden" name="answers[{{ $question->id }}]" x-model="code">
    {{-- 
    <p class="text-xs text-blue-400 mt-2">
        * Generado con datos de: <strong>{{ $staticName }}</strong>
    </p>
     --}}
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('systemCodeGenerator', (config) => ({
            code: config.initialCode,
            userId: config.userId,
            year: config.currentYear,
            staticName: config.staticName,
            questionId: '{{ $question->id }}', // <--- Necesitamos pasar esto
            entryId: '{{ request()->route('entry') }}', // <--- Intentamos obtener el ID si es edición (ajusta según tu ruta)
            isLoading: false, // Para mostrar un spinner si quieres

            initComponent() {
                if (!this.code) {
                    this.generate();
                }
            },

            // Hacemos la función ASYNC para poder esperar al servidor
            async generate() {
                // --- 1. LÓGICA DE CÁLCULO BASE (IGUAL QUE ANTES) ---
                let nameVal = '';

                // Buscar input físico
                let nameInput = document.querySelector('[data-code-tag="source_name"]');
                if (nameInput) {
                    nameVal = nameInput.value;
                } else {
                    nameVal = this.staticName;
                }

                // Buscar input tipo
                let typeInput = document.querySelector('[data-code-tag="source_type"]');
                let typeVal = 'X';
                if (typeInput) {
                    if (typeInput.tomselect) typeVal = typeInput.tomselect.getValue() || 'X';
                    else typeVal = typeInput.value || 'X';
                }

                // Calcular iniciales
                let initials = 'XX';
                if (nameVal) {
                    initials = nameVal.trim().split(/\s+/).map(w => w.charAt(0)).join('')
                        .toUpperCase();
                }

                // --- CÓDIGO BASE ---
                let baseCode = `${this.userId}_${initials}_${this.year}_${typeVal}`;

                // --- 2. CONSULTA AL SERVIDOR (LA NOVEDAD) ---
                // Llamamos a la ruta que creamos en el Paso 1
                try {
                    this.isLoading = true;

                    // Construimos la URL. Ajusta '/api/validate-folio' si usaste otro prefijo
                    const url = new URL("{{ route('api.validate.folio') }}");
                    url.search = new URLSearchParams({
                        code: baseCode,
                        question_id: this.questionId
                    });
                    // Si tenemos entryId (edición), lo enviamos para no bloquearnos a nosotros mismos
                    if (this.entryId) url += `&entry_id=${this.entryId}`;

                    let response = await fetch(url);
                    let data = await response.json();

                    // --- 3. ACTUALIZAMOS CON EL CÓDIGO ÚNICO ---
                    this.code = data.unique_code;

                } catch (error) {
                    console.error('Error validando folio:', error);
                    // Fallback: Si falla el servidor, usamos el base al menos
                    this.code = baseCode;
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
</script>
