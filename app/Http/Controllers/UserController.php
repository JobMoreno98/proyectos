<?php

namespace App\Http\Controllers;

use App\Models\ViewDatosGenerales;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        // Cambiamos all() por orderBy()->get()
        $usuarios = ViewDatosGenerales::all()->sortBy(function ($item) {
            // 1. Obtenemos el apellido (asumiendo que estás usando el Accessor datos_limpios)
            $apellido = $item->datos_limpios['Apellido Paterno'] ?? '';

            // 2. Str::ascii() convierte temporalmente "Ávila" en "Avila" y "Cándida" en "Candida"
            // strtolower asegura que mayúsculas y minúsculas no rompan el orden
            return strtolower(Str::ascii($apellido));
        })->values();
        return view('usuarios.index', compact('usuarios'));
    }
}
