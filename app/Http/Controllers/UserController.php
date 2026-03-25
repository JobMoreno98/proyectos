<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ViewDatosGenerales;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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
    public function asignar($id)
    {
        $user = User::findOrfail($id);
        $roles = Role::where('name', '!=', 'super_admin')->get();
        return view('usuarios.asignar', compact('user', 'roles'));
    }
    public function add_role(Request $request, $id)
    {
        $request->validate([
            'roles'   => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::findOrFail($id);

        $user->syncRoles($request->roles);

        return redirect()
            ->route('asignar.rol', $user->id)
            ->with('success', 'Roles actualizados correctamente.');
    }
}
