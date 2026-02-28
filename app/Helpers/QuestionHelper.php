<?php

namespace App\Helpers;

use App\Models\Entry;
use App\Models\Sections;

class QuestionHelper
{
    public static function prepare($question, $existingAnswers = [])
    {
        $errorKey = "answers.{$question->id}";
        $savedValue = $existingAnswers[$question->id] ?? null;
        $defaultValue = $question->options['default_value'] ?? '';
        $finalValue = old($errorKey, $savedValue ?? $defaultValue);

        $isGeneratedCode = ($question->options['code_tag'] ?? '') === 'generated_code';

        $componentName = $isGeneratedCode ? 'inputs.system-code' : 'inputs.' . $question->type;

        if (!view()->exists("components.{$componentName}")) {
            $componentName = 'inputs.text';
        }

        $isDependent = $question->options['is_dependent'] ?? false;
        $parentId = $question->options['depends_on_question_id'] ?? null;
        $expectedValue = $question->options['depends_on_value'] ?? null;
        
        $subForm = null;

        if ($question->type === 'sub_form') {
            $targetSectionId = $question->options['target_section_id'] ?? null;

            if ($targetSectionId) {
                $childSection = Sections::with('questions')->find($targetSectionId);

                $childEntryId = $existingAnswers[$question->id] ?? null;

                $childAnswers = [];

                if ($childEntryId) {
                    $childEntry = Entry::with('answers')->find($childEntryId);

                    if ($childEntry) {
                        $childAnswers = $childEntry->answers->pluck('value', 'question_id')->toArray();
                    }
                }

                $subForm = [
                    'section' => $childSection,
                    'answers' => $childAnswers,
                ];
            }
        }

        return [
            'model' => $question,
            'component' => $componentName,
            'value' => $finalValue,
            'type' => $question->type,
            'isDependent' => $isDependent,
            'parentId' => $parentId,
            'expectedValue' => $expectedValue,
            'subForm' => $subForm,
        ];
    }
}
