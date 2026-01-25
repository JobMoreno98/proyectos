<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\DashboardController;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'datos.generales'])->group(function () {

    Route::resource('proyectos', AnswerController::class)->except('create');

    Route::get('/seccion/{categoria}', [CategoriasController::class, 'show'])->name('seccion.show');

    Route::get('/proyectos/{id}', [AnswerController::class, 'create'])->name('proyectos.create');

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

require __DIR__ . '/settings.php';
