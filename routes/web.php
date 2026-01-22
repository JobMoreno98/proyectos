<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\UserController;
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
});
