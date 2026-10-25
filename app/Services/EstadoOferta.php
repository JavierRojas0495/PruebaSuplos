<?php

declare(strict_types=1);

namespace App\Services;

final class EstadoOferta
{
    /**
     * Descripcion: Determina si la licitación está programada, en curso o cerrada.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function calcular(
        string $fechaInicio,
        string $horaInicio,
        string $fechaCierre,
        string $horaCierre,
        ?\DateTimeImmutable $ahora = null
    ): string {
        $zona = new \DateTimeZone(date_default_timezone_get());
        $ahora = $ahora ?? new \DateTimeImmutable('now', $zona);
        $inicio = new \DateTimeImmutable(substr($fechaInicio, 0, 10) . ' ' . self::hora($horaInicio), $zona);
        $cierre = new \DateTimeImmutable(substr($fechaCierre, 0, 10) . ' ' . self::hora($horaCierre), $zona);

        if ($ahora < $inicio) {
            return 'Programada';
        }
        if ($ahora > $cierre) {
            return 'Cerrada';
        }

        return 'En curso';
    }

    /**
     * Descripcion: Completa la hora con segundos cuando llega en formato HH:mm.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private static function hora(string $hora): string
    {
        return strlen($hora) === 5 ? $hora . ':00' : $hora;
    }
}
