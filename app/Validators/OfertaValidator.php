<?php

declare(strict_types=1);

namespace App\Validators;

final class OfertaValidator
{
    public const MONEDAS = ['COP', 'USD', 'EUR'];

    /**
     * Descripcion: Revisa los campos obligatorios, los formatos y que el cierre sea posterior al inicio.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function validar(array $datos): array
    {
        $errores = [];

        $objeto = self::texto($datos['objeto'] ?? null);
        if ($objeto === '') {
            $errores['objeto'] = 'El objeto es obligatorio y no puede ser solo espacios.';
        } elseif (mb_strlen($objeto) > 150) {
            $errores['objeto'] = 'El objeto no puede superar 150 caracteres.';
        }

        $descripcion = self::texto($datos['descripcion'] ?? null);
        if ($descripcion === '') {
            $errores['descripcion'] = 'La descripción es obligatoria y no puede ser solo espacios.';
        } elseif (mb_strlen($descripcion) > 400) {
            $errores['descripcion'] = 'La descripción no puede superar 400 caracteres.';
        }

        $moneda = strtoupper(self::texto($datos['moneda'] ?? null));
        if ($moneda === '') {
            $errores['moneda'] = 'La moneda es obligatoria.';
        } elseif (!in_array($moneda, self::MONEDAS, true)) {
            $errores['moneda'] = 'La moneda solo puede ser COP, USD o EUR.';
        }

        $presupuesto = self::texto($datos['presupuesto'] ?? null);
        if ($presupuesto === '') {
            $errores['presupuesto'] = 'El presupuesto es obligatorio.';
        } elseif (!preg_match('/^\d{1,13}(\.\d{1,2})?$/', $presupuesto)) {
            $errores['presupuesto'] = 'El presupuesto solo admite números y punto, con máximo dos decimales.';
        } elseif (bccomp($presupuesto, '0', 2) !== 1) {
            $errores['presupuesto'] = 'El presupuesto debe ser mayor que 0.';
        }

        $actividad = self::texto($datos['actividad_id'] ?? null);
        if ($actividad === '' || !preg_match('/^[1-9]\d*$/', $actividad)) {
            $errores['actividad_id'] = 'La actividad es obligatoria.';
        }

        $fechaInicio = self::fecha($datos['fecha_inicio'] ?? null);
        $fechaCierre = self::fecha($datos['fecha_cierre'] ?? null);
        $horaInicio = self::hora($datos['hora_inicio'] ?? null);
        $horaCierre = self::hora($datos['hora_cierre'] ?? null);

        if ($fechaInicio === null) {
            $errores['fecha_inicio'] = 'La fecha de inicio es obligatoria y debe tener formato AAAA-MM-DD.';
        }
        if ($fechaCierre === null) {
            $errores['fecha_cierre'] = 'La fecha de cierre es obligatoria y debe tener formato AAAA-MM-DD.';
        }
        if ($horaInicio === null) {
            $errores['hora_inicio'] = 'La hora de inicio es obligatoria y debe tener formato HH:mm.';
        }
        if ($horaCierre === null) {
            $errores['hora_cierre'] = 'La hora de cierre es obligatoria y debe tener formato HH:mm.';
        }

        if ($fechaInicio !== null && $fechaCierre !== null && $fechaCierre < $fechaInicio) {
            $errores['fecha_cierre'] = 'La fecha de cierre no puede ser anterior a la fecha de inicio.';
        }

        if (
            $fechaInicio !== null
            && $fechaCierre !== null
            && $horaInicio !== null
            && $horaCierre !== null
            && !isset($errores['fecha_cierre'])
        ) {
            $inicio = $fechaInicio . ' ' . $horaInicio;
            $cierre = $fechaCierre . ' ' . $horaCierre;
            if ($inicio >= $cierre) {
                $errores['hora_cierre'] = 'La fecha y hora de cierre deben ser posteriores al inicio.';
            }
        }

        return $errores;
    }

    /**
     * Descripcion: Deja los campos de la oferta listos para guardarse.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function normalizar(array $datos): array
    {
        $presupuesto = self::texto($datos['presupuesto'] ?? null);
        if (preg_match('/^\d{1,13}(\.\d{1,2})?$/', $presupuesto)) {
            $presupuesto = bcadd($presupuesto, '0', 2);
        }

        return [
            'objeto' => self::texto($datos['objeto'] ?? null),
            'descripcion' => self::texto($datos['descripcion'] ?? null),
            'moneda' => strtoupper(self::texto($datos['moneda'] ?? null)),
            'presupuesto' => $presupuesto,
            'actividad_id' => (int) self::texto($datos['actividad_id'] ?? null),
            'fecha_inicio' => self::fecha($datos['fecha_inicio'] ?? null),
            'hora_inicio' => self::hora($datos['hora_inicio'] ?? null),
            'fecha_cierre' => self::fecha($datos['fecha_cierre'] ?? null),
            'hora_cierre' => self::hora($datos['hora_cierre'] ?? null),
        ];
    }

    /**
     * Descripcion: Convierte un valor a texto sin espacios laterales.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private static function texto(mixed $valor): string
    {
        if (!is_scalar($valor)) {
            return '';
        }

        return trim((string) $valor);
    }

    /**
     * Descripcion: Acepta solo una fecha real con formato AAAA-MM-DD.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private static function fecha(mixed $valor): ?string
    {
        $texto = self::texto($valor);
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        if ($fecha === false || $fecha->format('Y-m-d') !== $texto) {
            return null;
        }

        return $texto;
    }

    /**
     * Descripcion: Acepta solo una hora real de 24 horas.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private static function hora(mixed $valor): ?string
    {
        $texto = self::texto($valor);
        if (preg_match('/^\d{2}:\d{2}$/', $texto)) {
            $texto .= ':00';
        }

        $hora = \DateTimeImmutable::createFromFormat('!H:i:s', $texto);
        if ($hora === false || $hora->format('H:i:s') !== $texto) {
            return null;
        }

        return $texto;
    }
}
