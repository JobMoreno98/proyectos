<?php

namespace App\Helpers;

use App\Models\CatalogItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CatalogProvider
{
    public static function get(string $catalogName): array
    {
        // Cacheamos por 24 horas
        return \Illuminate\Support\Facades\Cache::remember("catalog_{$catalogName}", 60 * 60 * 24, function () use ($catalogName) {

            // 2. Si no es académica, buscamos en la TABLA UNIVERSAL
            $items = CatalogItem::byType($catalogName)
                ->orderBy('name')
                ->pluck('name', 'id') // Usamos el ID del registro como value
                ->toArray();

            // Si encontramos datos, los retornamos
            if (!empty($items)) {
                return $items;
            }

            // 3. Si no hay nada en BD, fallback a listas estáticas (si tienes alguna)
            return match ($catalogName) {
                default => []
            };
        });
    }
}
