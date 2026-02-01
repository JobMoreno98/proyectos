<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\UserController;
use App\Models\Answer;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::resource('proyectos', AnswerController::class);
    Route::get('/datos-generales', [UserController::class, 'datos_generales'])->name('datos.generales');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/api/validate-folio', function (Request $request) {

        $questionId = $request->input('question_id');
        $baseCode = $request->input('code');
        $entryId = $request->input('entry_id'); // Para excluir el propio registro al editar

        // 1. Si no hay código, retornamos vacío
        if (!$baseCode) return response()->json(['unique_code' => '']);

        // 2. Función recursiva para encontrar el siguiente libre
        $finalCode = $baseCode;
        $counter = 1;

        // Buscamos si existe algun 'Answer' para ESTA pregunta con ESTE valor
        // Excluyendo nuestro propio entry_id (si estamos editando)
        while (Answer::where('question_id', $questionId)
            ->where('value', $finalCode)
            ->when($entryId, fn($q) => $q->where('entry_id', '!=', $entryId))
            ->exists()
        ) {
            $finalCode = $baseCode . '_' . $counter;
            $counter++;
        }

        return response()->json(['unique_code' => $finalCode]);
    })->name('api.validate.folio');
});
