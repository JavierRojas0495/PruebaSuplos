<?php

declare(strict_types=1);

namespace App\Core;

final class Env
{
    /**
     * Descripcion: Carga el archivo .env sin reemplazar variables que ya existen.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lineas = file($path, FILE_IGNORE_NEW_LINES);
        if ($lineas === false) {
            return;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $partes = explode('=', $linea, 2);
            $clave = trim($partes[0]);
            $valor = trim($partes[1] ?? '');
            $valor = trim($valor, "\"'");

            if ($clave === '' || getenv($clave) !== false) {
                continue;
            }

            $_ENV[$clave] = $valor;
            putenv($clave . '=' . $valor);
        }
    }

    /**
     * Descripcion: Devuelve una variable de entorno o su valor por defecto.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function get(string $clave, ?string $defecto = null): ?string
    {
        $valor = $_ENV[$clave] ?? getenv($clave);
        if ($valor === false || $valor === null || $valor === '') {
            return $defecto;
        }

        return (string) $valor;
    }

    /**
     * Descripcion: Indica si la aplicación está en modo de depuración.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function debug(): bool
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}
