<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * Descripcion: Devuelve el método HTTP de la petición.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function metodo(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Descripcion: Lee y recorta un parámetro de la consulta.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function consulta(string $clave, string $defecto = ''): string
    {
        if (!isset($_GET[$clave])) {
            return $defecto;
        }

        return trim((string) $_GET[$clave]);
    }

    /**
     * Descripcion: Decodifica el cuerpo JSON de la petición.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function json(): array
    {
        $crudo = file_get_contents('php://input');
        if ($crudo === false || trim($crudo) === '') {
            return [];
        }

        $datos = json_decode($crudo, true);

        return is_array($datos) ? $datos : [];
    }

    /**
     * Descripcion: Lee y recorta un campo del formulario.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function campo(string $clave, string $defecto = ''): string
    {
        if (!isset($_POST[$clave])) {
            return $defecto;
        }

        return trim((string) $_POST[$clave]);
    }

    /**
     * Descripcion: Reúne los campos de la oferta enviados por formulario.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function formularioOferta(): array
    {
        return [
            'objeto' => $this->campo('objeto'),
            'descripcion' => $this->campo('descripcion'),
            'moneda' => $this->campo('moneda'),
            'presupuesto' => $this->campo('presupuesto'),
            'actividad_id' => $this->campo('actividad_id'),
            'fecha_inicio' => $this->campo('fecha_inicio'),
            'hora_inicio' => $this->campo('hora_inicio'),
            'fecha_cierre' => $this->campo('fecha_cierre'),
            'hora_cierre' => $this->campo('hora_cierre'),
        ];
    }
}
