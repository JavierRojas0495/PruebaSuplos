<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /**
     * Descripcion: Responde JSON con el código de estado indicado.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function json(mixed $datos, int $estado = 200): void
    {
        self::cors();
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Descripcion: Descarga un archivo como adjunto.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function archivo(string $rutaAbsoluta, string $nombreDescarga): void
    {
        self::cors();
        http_response_code(200);
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string) filesize($rutaAbsoluta));
        header('Content-Disposition: attachment; filename="' . self::nombreSeguro($nombreDescarga) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($rutaAbsoluta);
    }

    /**
     * Descripcion: Descarga el contenido binario guardado en la base.
     * Fecha: 25/10/26
     * Autor: Javier Rojas
     */
    public static function contenido(string $bytes, string $nombreDescarga): void
    {
        self::cors();
        http_response_code(200);
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string) strlen($bytes));
        header('Content-Disposition: attachment; filename="' . self::nombreSeguro($nombreDescarga) . '"');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;
    }

    /**
     * Descripcion: Agrega las cabeceras que permiten consumir la API desde el frontend.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }

    /**
     * Descripcion: Limpia el nombre del archivo para la cabecera de descarga.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private static function nombreSeguro(string $nombre): string
    {
        $limpio = str_replace(['"', "\r", "\n"], '', $nombre);

        return $limpio === '' ? 'documento' : $limpio;
    }
}
