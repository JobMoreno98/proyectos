<?php

namespace App\Http\Requests;

use App\Models\Questions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSurveyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Cambia esto si necesitas lógica de permisos específica
    }

    /**
     * Get the validation rules that apply to the request.
     */


    public function rules(): array
    {
        $rules = [];
        $answers = $this->input('answers');
        $subAnswers = $this->input('sub_answers');


        // 1. Validaciones Estructurales (Base)
        $rules['section_ids'] = 'required|array';
        $rules['section_ids.*'] = 'exists:sections,id';
        $rules['answers'] = 'array';
        $rules['sub_answers'] = 'nullable|array';

        // 2. Obtenemos preguntas
        $targetSections = $this->input('section_ids', []);
        $questions = \App\Models\Questions::whereIn('section_id', $targetSections)->get();

        // 3. Reglas para preguntas NORMALES (Array 'answers')
        foreach ($questions as $question) {
            $isDependent = filter_var($question->options['is_dependent'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($isDependent) {
                $parentId = $question->options['depends_on_question_id'] ?? null;
                $expectedValue = $question->options['depends_on_value'] ?? null;

                // Buscamos qué respondió el usuario en el padre
                $actualValue = $answers[$parentId] ?? null;

                // Si la respuesta no es la esperada, la pregunta está oculta,
                // así que ignoramos todas sus reglas de validación.
                if ((string) $actualValue !== (string) $expectedValue) {
                    continue;
                }
            }
            // ---------------------------------

            $fieldKey = 'answers.' . $question->id;
            $fieldRules = [];

            // A. Regla base (Requerido/Nullable)
            if ($question->type !== 'file' && $question->type !== 'sub_form') {
                $fieldRules[] = $question->is_required ? 'required' : 'nullable';
            } else {
                $fieldRules[] = 'nullable';
            }

            // B. Regla Única (Tu lógica existente se mantiene igual)
            if ($question->is_unique) {

                $uniqueRule = Rule::unique('answers_view', 'respuesta')->where('question_id', $question->id)->where('user_id', '!=', Auth::user()->id);

                if ($this->isMethod('put') || $this->isMethod('patch')) {
                    $entryRouteParam = $this->route('proyecto'); // O 'entry', verifica tu ruta

                    $entryIdToIgnore = ($entryRouteParam instanceof \Illuminate\Database\Eloquent\Model)
                        ? $entryRouteParam->id
                        : $entryRouteParam;


                    if ($entryIdToIgnore) {
                        $uniqueRule->whereNot('entry_id', $entryIdToIgnore);
                    }
                }

                $fieldRules[] = $uniqueRule;
            }

            // C. Reglas por Tipo
            switch ($question->type) {

                case 'scored_text':
                    $fieldRules[] = 'array'; // El campo principal debe ser array

                    // Definimos si los hijos son requeridos
                    $subRequired = $question->is_required ? 'required' : 'nullable';
                    $min = $question->options['min_score'] ?? 0;
                    $max = $question->options['max_score'] ?? 10;

                    // Regla para el Puntaje
                    $rules["{$fieldKey}.score"] = [
                        $subRequired,
                        'numeric',
                        'integer',
                        "between:{$min},{$max}"
                    ];

                    // Regla para el Texto
                    $rules["{$fieldKey}.text"] = [
                        $subRequired,
                        'string',
                        'min:3' // Opcional: longitud mínima
                    ];
                    break;
                case 'text':
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:65535';
                    break;
                case 'number':
                    $fieldRules[] = 'integer';
                    $fieldRules[] = 'min:0';
                    break;
                case 'select':
                    $choices = $question->options['choices'] ?? [];
                    if (!empty($choices)) {
                        // MEJORA: Usamos Rule::in para evitar errores con comas
                        $fieldRules[] = Rule::in(array_column($choices, 'value'));
                    }
                    break;
                case 'file':
                    // Validación estricta de archivos
                    $fieldRules[] = 'file';
                    $fieldRules[] = 'max:10240'; // 10MB
                    // Si es PUT (edición), el archivo no es obligatorio si ya existe uno (lógica de negocio)
                    // Pero aquí asumimos nullable en PUT para no obligar a resubir
                    $fieldRules[] = $this->isMethod('put') ? 'nullable' : ($question->is_required ? 'required' : 'nullable');

                    $allowedFormats = $question->options['allowed_formats'] ?? 'pdf';
                    $fieldRules[] = 'mimes:' . str_replace(' ', '', $allowedFormats);
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    if (!empty($question->options['min_date'])) $fieldRules[] = 'after_or_equal:' . $question->options['min_date'];
                    if (!empty($question->options['max_date'])) $fieldRules[] = 'before_or_equal:' . $question->options['max_date'];
                    break;
                case 'catalog':
                    $catalogName = $question->options['catalog_name'] ?? '';
                    $validOptions = \App\Helpers\CatalogProvider::get($catalogName);
                    if (!empty($validOptions)) {
                        $fieldRules[] = Rule::in(array_keys($validOptions));
                    } else {
                        // Si el catálogo no existe o está vacío, prohibimos la entrada por seguridad
                        $fieldRules[] = 'prohibited';
                    }
                    break;
                case 'repeater_awards':
                    $fieldRules[] = 'array';

                    // Reglas internas
                    $rules["{$fieldKey}.*.nombre"] = 'required|string|max:255';

                    // VALIDACIÓN DINÁMICA DEL SELECT
                    // Extraemos los valores válidos de las opciones de la pregunta
                    $validChoices = array_column($question->options['choices'] ?? [], 'value');

                    if (!empty($validChoices)) {
                        // Usamos Rule::in para mayor seguridad
                        $rules["{$fieldKey}.*.tipo"] = ['required', \Illuminate\Validation\Rule::in($validChoices)];
                    } else {
                        // Fallback por si no configuraron opciones
                        $rules["{$fieldKey}.*.tipo"] = 'required';
                    }

                    break;
            }

            // Asignamos la regla solo si hay reglas generadas
            if (!empty($fieldRules)) {
                $rules[$fieldKey] = $fieldRules;
            }
        }

        // 4. Reglas para SUB-FORMULARIOS (Array 'sub_answers')
        // Iteramos solo las preguntas de tipo sub_form encontradas en el paso 2

        foreach ($questions->where('type', 'sub_form') as $parentQuestion) {

            $parentId = $parentQuestion->id;

            // Validamos que el contenedor del padre sea un array
            $rules["sub_answers.{$parentId}"] = 'nullable|array';

            $targetSectionId = $parentQuestion->options['target_section_id'] ?? null;
            if (!$targetSectionId) continue;

            // Cargamos la sección hija
            $childSection = \App\Models\Sections::with('questions')->find($targetSectionId);
            if (!$childSection) continue;

            foreach ($childSection->questions as $childQ) {

                // LA CLAVE CORRECTA: sub_answers.PADRE.HIJO
                $fieldKey = "sub_answers.{$parentId}.{$childQ->id}";
                $fieldRules = [];
                // Regla base
                if ($childQ->type !== 'file' && $childQ->type !== 'sub_form') {
                    $fieldRules[] = $childQ->is_required ? 'required' : 'nullable';
                } else {
                    $fieldRules[] = 'nullable';
                }


                // Tipos
                switch ($childQ->type) {

                    case 'scored_text':
                        $fieldRules[] = 'array';
                        $subRequired = $childQ->is_required ? 'required' : 'nullable';
                        $min = $childQ->options['min_score'] ?? 0;
                        $max = $childQ->options['max_score'] ?? 10;

                        $rules["{$fieldKey}.score"] = [$subRequired, 'numeric', 'integer', "between:{$min},{$max}"];
                        $rules["{$fieldKey}.text"] = [$subRequired, 'string', 'min:3'];
                        break;

                    case 'text':
                    case 'textarea':
                        $fieldRules[] = 'string';
                        $fieldRules[] = 'max:65535';
                        break;
                    case 'number':
                        $fieldRules[] = 'integer';
                        $fieldRules[] = 'min:0';
                        break;
                    case 'select':
                        $choices = $childQ->options['choices'] ?? [];
                        if ($choices) {
                            $fieldRules[] = Rule::in(array_column($choices, 'value'));
                        }
                        break;
                    case 'date':
                        $fieldRules[] = 'date';
                        break;
                    // Importante: Laravel no maneja bien la subida de archivos en arrays anidados profundos
                    // a veces. Verifica si tus sub-forms tienen archivos.
                    case 'file':
                        $fieldRules[] = $this->isMethod('put') ? 'nullable' : ($childQ->is_required ? 'required' : 'nullable');
                        $fieldRules[] = 'file';
                        $fieldRules[] = 'max:10240';
                        $formats = $childQ->options['allowed_formats'] ?? 'pdf';
                        $fieldRules[] = 'mimes:' . str_replace(' ', '', $formats);
                        break;

                    case 'repeater_awards':
                        $fieldRules[] = 'array';

                        // Reglas internas
                        $rules["{$fieldKey}.*.nombre"] = 'required|string|max:255';

                        // VALIDACIÓN DINÁMICA DEL SELECT
                        // Extraemos los valores válidos de las opciones de la pregunta
                        $validChoices = array_column($question->options['choices'] ?? [], 'value');

                        if (!empty($validChoices) &&  count($validChoices) > 0) {
                            // Usamos Rule::in para mayor seguridad
                            $rules["{$fieldKey}.*.tipo"] = ['required', \Illuminate\Validation\Rule::in($validChoices)];
                        } else {
                            // Fallback por si no configuraron opciones
                            $rules["{$fieldKey}.*.tipo"] = 'required';
                        }

                        break;
                }

                $rules[$fieldKey] = $fieldRules;
            }
        }

        return $rules;
    }


    /**
     * Personalizar los nombres de los atributos para los errores.
     * Esto hace que el error diga "El campo Fecha de Nacimiento es obligatorio"
     * en lugar de "El campo answers.5 es obligatorio".
     */
    public function attributes(): array
    {
        $attributes = [];

        // 1. Optimización: Cargamos todas las preguntas o filtramos por las secciones actuales
        // Si tienes pocas preguntas, all() está bien. Si son muchas, mejor filtrar como en rules()
        $targetSections = $this->input('section_ids', []);

        // Si no hay secciones en el input, cargamos todo (fallback)
        if (empty($targetSections)) {
            $questions = \App\Models\Questions::all();
        } else {
            $questions = \App\Models\Questions::whereIn('section_id', $targetSections)->get();
        }

        foreach ($questions as $question) {
            // A. Mapeo para preguntas normales (answers.14)
            // A. Mapeo para preguntas normales
            $key = 'answers.' . $question->id;
            $attributes[$key] = $question->label;

            if ($question->type === 'scored_text') {
                $attributes["{$key}.score"] = 'Puntuación de ' . $question->label;
                $attributes["{$key}.text"]  = 'Justificación de ' . $question->label;
            }

            // B. Mapeo para SUB-FORMULARIOS (sub_answers.31.26)
            if ($question->type === 'sub_form') {

                $targetSectionId = $question->options['target_section_id'] ?? null;

                if ($targetSectionId) {
                    // Buscamos la sección hija y sus preguntas
                    // Usamos 'with' para optimizar la consulta
                    $childSection = \App\Models\Sections::with('questions')->find($targetSectionId);

                    if ($childSection) {
                        foreach ($childSection->questions as $childQ) {

                            $childKey = "sub_answers.{$question->id}.{$childQ->id}";
                            $attributes[$childKey] = $childQ->label;

                            // --- B.1 CORRECCIÓN: SCORED_TEXT DENTRO DE SUB-FORM ---
                            if ($childQ->type === 'scored_text') {
                                $attributes["{$childKey}.score"] = 'Puntuación de ' . $childQ->label;
                                $attributes["{$childKey}.text"]  = 'Justificación de ' . $childQ->label;
                            }
                            // ------------------------------------------------------

                            if ($childQ->type === 'repeater_awards') {
                                $attributes["{$childKey}.*.nombre"] = 'Nombre';
                                $attributes["{$childKey}.*.tipo"]   = 'Tipo';
                            }
                        }
                    }
                }
            }
        }

        return $attributes;
    }
    /**
     * Mensajes personalizados opcionales.
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo ":attribute" es obligatorio.',
            'date' => 'El campo ":attribute" no es una fecha válida.',
            'after_or_equal' => 'La fecha de ":attribute" debe ser posterior o igual a :date.',
            'before_or_equal' => 'La fecha de ":attribute" debe ser anterior o igual a :date.',
            'in' => 'La opción seleccionada en ":attribute" no es válida.',
            'file' => 'El archivo subido en ":attribute" no es válido.',
            'max' => 'El valor de ":attribute" excede el límite permitido.',
            'unique' => 'El campo ":attribute" ya ha sido registrado por otro usuario. Si surge algún problema, favor de contactar a un administrador.',
            'min' => 'El campo ":attribute" debe de der un número entero mayor o igual a cero.'
        ];
    }
}
