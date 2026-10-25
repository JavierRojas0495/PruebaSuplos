<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Actividad;

final class ActividadController
{
    /**
     * Descripcion: Busca actividades de la maestra por texto o código y devuelve hasta 30, ordenadas por código de producto.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function index(Request $solicitud): void
    {
        $termino = $solicitud->consulta('q');
        $consulta = Actividad::query()->select([
            'id',
            'codigo_producto',
            'producto',
            'clase',
            'familia',
            'segmento',
        ]);

        if ($termino !== '') {
            $como = '%' . $this->escapar($termino) . '%';
            $consulta->where(function ($sub) use ($termino, $como) {
                $sub->where('producto', 'like', $como)
                    ->orWhere('clase', 'like', $como)
                    ->orWhere('familia', 'like', $como);
                if (preg_match('/^\d+$/', $termino)) {
                    $sub->orWhere('codigo_producto', (int) $termino);
                }
            });
        }

        $actividades = $consulta->orderBy('codigo_producto')->limit(30)->get();

        Response::json(['data' => $actividades]);
    }

    /**
     * Descripcion: Escapa los comodines de LIKE para que el texto buscado se compare de forma literal.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function escapar(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }
}
