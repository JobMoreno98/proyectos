<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluacionesController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\isAdmin;
use App\Models\Answer;
use App\Models\AnswerFullView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'datos.generales'])->group(function () {

    Route::resource('proyectos', AnswerController::class)->except('create');

    Route::resource('evaluacion', EvaluacionesController::class)->except('show', 'index', 'destroy', 'create');

    Route::get('/evaluacion/{id}', [EvaluacionesController::class, 'create'])->name('evaluacion.create');

    Route::get('/ver-informacion/{id}', [EvaluacionesController::class, 'show'])->name('infor.form');

    Route::get('/asignar/{id}', [AnswerController::class, 'asignar'])->name('asginar.proyecto');

    Route::get('/seccion/{categoria}', [CategoriasController::class, 'show'])->name('seccion.show');

    Route::get('/proyectos/{id}', [AnswerController::class, 'create'])->name('proyectos.create');

    Route::get('/definitivo/{id}', [AnswerController::class, 'definitivo'])->name('proyectos.send');

    Route::get('/imprimir/{id}', [AnswerController::class, 'imprimir'])->name('proyectos.print');

    Route::get('/api/validate-folio', function (Request $request) {

        $questionId = $request->input('question_id');
        $baseCode = $request->input('code');
        $entryId = $request->input('entry_id'); // Para excluir el propio registro al editar

        // 1. Si no hay código, retornamos vacío
        if (!$baseCode) return response()->json(['unique_code' => '']);


        // 2. Función recursiva para encontrar el siguiente libre
        $finalCode = $baseCode;

        $counter = 0;
        // Buscamos si existe algun 'Answer' para ESTA pregunta con ESTE valor
        // Excluyendo nuestro propio entry_id (si estamos editando)
        do {
            $finalCode = $counter === 0 ? $baseCode : $baseCode . '_' . $counter;
            $counter++;
        } while (
            AnswerFullView::where('question_id', $questionId)
            ->where('respuesta', $finalCode)
            ->when($entryId, fn($q) => $q->where('entry_id', '!=', $entryId))
            ->exists()
        );


        return response()->json(['unique_code' => $finalCode]);
    })->name('api.validate.folio');

    Route::resource('usuarios', UserController::class)->middleware(isAdmin::class)->only('index');
    Route::get('/asignar-rol/{id}', [UserController::class, 'asignar'])->name('asignar.rol')->middleware(isAdmin::class);
    Route::put('/asignar-rol/{id}', [UserController::class, 'add_role'])->name('user.assignRole')->middleware(isAdmin::class);
    Route::get('/exportar-datos', [ExportController::class, 'index'])->name('export.data')->middleware(isAdmin::class);
});

require __DIR__ . '/settings.php';
