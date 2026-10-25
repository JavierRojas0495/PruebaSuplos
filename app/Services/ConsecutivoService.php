<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Capsule\Manager as Capsule;

final class ConsecutivoService
{
    /**
     * Descripcion: Reserva el siguiente consecutivo del año con formato O-0001-26.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function siguiente(): string
    {
        $anio = date('y');

        $fila = Capsule::table('secuencias')->where('anio', $anio)->lockForUpdate()->first();
        if ($fila === null) {
            Capsule::table('secuencias')->insert([
                'anio' => $anio,
                'ultimo' => 1,
            ]);
            $numero = 1;
        } else {
            $numero = ((int) $fila->ultimo) + 1;
            Capsule::table('secuencias')->where('anio', $anio)->update(['ultimo' => $numero]);
        }

        return sprintf('O-%04d-%s', $numero, $anio);
    }
}
