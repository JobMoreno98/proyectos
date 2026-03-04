<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Información {{ $seccion->title }}</title>
    <style>
        @page {
            margin: 30px 25px 50px 25px;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.3;
        }

        footer {
            position: fixed;
            bottom: -35px;
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .footer-table td {
            padding: 0;
            border: none;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .page-number:after {
            content: counter(page);
        }

        .header {
            background-color: #2563eb;
            color: #ffffff;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 14px;
        }

        .question-box {
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            background-color: #f9fafb;
            border-radius: 4px;
            margin-bottom: 6px;
            page-break-inside: avoid;
        }

        .question-label {
            font-weight: bold;
            color: #4b5563;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 3px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 2px;
        }

        .question-value {
            color: #111827;
            font-size: 12px;
        }

        .subform-container {
            margin-top: 6px;
            margin-bottom: 6px;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            padding: 5px;
        }

        .subform-title {
            background-color: #eff6ff;
            color: #1e40af;
            padding: 5px 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            margin: -5px -5px 6px -5px;
            border-bottom: 1px solid #bfdbfe;
            border-radius: 3px 3px 0 0;
        }

        .text-muted {
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>

<body>

    <footer>
        <table class="footer-table">
            <tr>
                <td class="text-left">www.cucsh.udg.mx</td>
                <td class="text-right">Página <span class="page-number"></span></td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="header">
            Información {{ $seccion->title }}
        </div>

        @foreach ($seccion->questions as $question)
            @php
                if ($question->type === 'file') {
                    continue;
                }

                $answer = $answersMap[$question->id] ?? null;
                $val = $answer ? $answer->value : null;

                // VALIDACIÓN REAL DE DEPENDENCIA PARA PREGUNTA NORMAL
                $isDependent = filter_var($question->options['is_dependent'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($isDependent) {
                    $parentId = $question->options['depends_on_question_id'] ?? null;
                    $expectedVal = trim(strtolower($question->options['depends_on_value'] ?? ''));

                    $parentAnswerObj = $answersMap[$parentId] ?? null;
                    $rawActualVal = $parentAnswerObj ? $parentAnswerObj->value : null;

                    $match = false;
                    if (is_array($rawActualVal)) {
                        $actualArray = array_map(fn($v) => trim(strtolower((string) $v)), $rawActualVal);
                        $match = in_array($expectedVal, $actualArray);
                    } else {
                        $match = trim(strtolower((string) $rawActualVal)) === $expectedVal;
                    }

                    // Si no se cumple la condición del padre, la saltamos
                    if (!$match) {
                        continue;
                    }
                }
            @endphp

            @if ($question->type === 'sub_form')
                @php
                    $targetSectionId = $question->options['target_section_id'] ?? null;
                    $childEntryId = $val;
                    $childSection = $targetSectionId
                        ? \App\Models\Sections::with(['questions' => fn($q) => $q->orderBy('sort_order')])->find(
                            $targetSectionId,
                        )
                        : null;

                    $childAnswersMap = [];
                    if ($childEntryId && ($childEntry = \App\Models\Entry::with('answers')->find($childEntryId))) {
                        $childAnswersMap = $childEntry->answers->keyBy('question_id');
                    }
                @endphp

                @if ($childSection && $childEntryId)
                    @php
                        // INSPECCIÓN PREVIA PARA SUB-FORMULARIOS
                        $hasPrintableQuestions = false;

                        foreach ($childSection->questions as $checkQ) {
                            if ($checkQ->type === 'file') {
                                continue;
                            }

                            $isChildDep = filter_var(
                                $checkQ->options['is_dependent'] ?? false,
                                FILTER_VALIDATE_BOOLEAN,
                            );
                            $childMatch = true;

                            if ($isChildDep) {
                                $childParentId = $checkQ->options['depends_on_question_id'] ?? null;
                                $childExpected = trim(strtolower($checkQ->options['depends_on_value'] ?? ''));

                                $childParentAns = $childAnswersMap[$childParentId] ?? null;
                                $rawChildActual = $childParentAns ? $childParentAns->value : null;

                                if (is_array($rawChildActual)) {
                                    $actualArr = array_map(fn($v) => trim(strtolower((string) $v)), $rawChildActual);
                                    $childMatch = in_array($childExpected, $actualArr);
                                } else {
                                    $childMatch = trim(strtolower((string) $rawChildActual)) === $childExpected;
                                }
                            }

                            if (!$isChildDep || $childMatch) {
                                $hasPrintableQuestions = true;
                                break;
                            }
                        }
                    @endphp

                    @if ($hasPrintableQuestions)
                        <div class="subform-container">
                            <div class="subform-title">{{ $childSection->title }}</div>

                            @foreach ($childSection->questions as $childQ)
                                @php
                                    if ($childQ->type === 'file') {
                                        continue;
                                    }

                                    $childAnswer = $childAnswersMap[$childQ->id] ?? null;
                                    $childVal = $childAnswer ? $childAnswer->value : null;

                                    // VALIDACIÓN REAL DE DEPENDENCIA PARA PREGUNTA HIJA
                                    $isChildDependent = filter_var(
                                        $childQ->options['is_dependent'] ?? false,
                                        FILTER_VALIDATE_BOOLEAN,
                                    );
                                    if ($isChildDependent) {
                                        $childParentId = $childQ->options['depends_on_question_id'] ?? null;
                                        $childExpected = trim(strtolower($childQ->options['depends_on_value'] ?? ''));

                                        $childParentAns = $childAnswersMap[$childParentId] ?? null;
                                        $rawChildActual = $childParentAns ? $childParentAns->value : null;

                                        $childMatchFinal = false;
                                        if (is_array($rawChildActual)) {
                                            $actualArr = array_map(
                                                fn($v) => trim(strtolower((string) $v)),
                                                $rawChildActual,
                                            );
                                            $childMatchFinal = in_array($childExpected, $actualArr);
                                        } else {
                                            $childMatchFinal =
                                                trim(strtolower((string) $rawChildActual)) === $childExpected;
                                        }

                                        if (!$childMatchFinal) {
                                            continue;
                                        }
                                    }
                                @endphp

                                <div class="question-box">
                                    <div class="question-label">{{ $childQ->label }}</div>
                                    <div class="question-value">
                                        @include('pdf.partials.render-value', [
                                            'type' => $childQ->type,
                                            'value' => $childVal,
                                            'options' => $childQ->options,
                                        ])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            @else
                <div class="question-box">
                    <div class="question-label">{{ $question->label }}</div>
                    <div class="question-value">
                        @php
                            $esPreguntaProyecto = strtolower(trim($question->label)) === 'proyecto';
                        @endphp

                        @if ($esPreguntaProyecto && $val)
                            @php
                                // IMPORTANTE: Cambia '\App\Models\Proyecto' por el nombre real de tu modelo
                                // y 'nombre' por el nombre de la columna donde guardas el título.
                                $proyectoDB = \App\Models\AnswerFullView::select('respuesta')
                                    ->where('entry_id', $val)
                                    ->where('pregunta','Título del Proyecto')
                                    ->value('respuesta');
                                $tituloProyecto = $proyectoDB
                                    ? $proyectoDB
                                    : "Proyecto no encontrado (ID: $val)";
                            @endphp

                            {{ $tituloProyecto }}
                        @else
                            @include('pdf.partials.render-value', [
                                'type' => $question->type,
                                'value' => $val,
                                'options' => $question->options,
                            ])
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </main>

</body>

</html>
