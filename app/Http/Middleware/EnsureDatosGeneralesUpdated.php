<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Categorias; // Asegúrate de tener este import
use App\Models\Sections;
use Illuminate\Database\Eloquent\Model;

class EnsureDatosGeneralesUpdated
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasUpdatedProfileThisYear()) {
            return $next($request);
        }

        if ($request->routeIs('dashboard', 'logout')) {
            return $next($request);
        }
        $targetCategoria = null;

        if ($request->routeIs('categorias.show')) {
            $param = $request->route('categoria');
            if ($param instanceof Categorias) {
                $targetCategoria = $param;
            } elseif (is_numeric($param)) {
                $targetCategoria = Categorias::find($param);
            }
        }

        // Casos de Respodner el formulario

        // CASO A: Guardando (POST)
        if ($request->routeIs('answers.store')) {
            $catId = $request->input('categoria_id');
            if ($catId) {
                $targetCategoria = Categorias::find($catId);
            }
        }
        // CASO B: Show (GET)
        if ($request->routeIs('answers.show')) {
            $seccionId = $request->route('answer');
            $seccion = Sections::find($seccionId);
            //dd($seccion->categoria_id);
            if ($seccion->categoria_id) {
                $targetCategoria = Categorias::find($seccion->categoria_id);
            }
        }

        // 4. LA COMPARACIÓN FINAL
        if ($targetCategoria) {
            $tituloBD = trim(mb_strtolower($targetCategoria->titulo));
            $tituloConstante = trim(mb_strtolower(Categorias::DATOS_GENERALES));
            if ($tituloBD === $tituloConstante) {
                return $next($request);
            }
        }

        return redirect()
            ->route('dashboard')
            ->with('warning', '⚠️ Acceso restringido: Debes completar "Datos Generales" primero.');
    }
}
