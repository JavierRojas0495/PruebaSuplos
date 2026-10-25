<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;

final class ArchivoService
{
    /**
     * Descripcion: Comprueba que el archivo sea un PDF o ZIP real y respete el tamaño permitido.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function validar(array $archivo, bool $exigirCargaHttp = true): array
    {
        $codigo = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($codigo === UPLOAD_ERR_NO_FILE) {
            return ['archivo' => 'El archivo es obligatorio.'];
        }
        if ($codigo !== UPLOAD_ERR_OK) {
            return ['archivo' => 'No se pudo recibir el archivo.'];
        }

        $nombre = (string) ($archivo['name'] ?? '');
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (!in_array($extension, ['pdf', 'zip'], true)) {
            return ['archivo' => 'Solo se permiten archivos PDF o ZIP.'];
        }

        $maximo = (int) Env::get('UPLOAD_MAX_MB', '10') * 1024 * 1024;
        $tamano = (int) ($archivo['size'] ?? 0);
        if ($tamano <= 0 || $tamano > $maximo) {
            return ['archivo' => 'El archivo debe pesar entre 1 byte y ' . Env::get('UPLOAD_MAX_MB', '10') . ' MB.'];
        }

        $temporal = (string) ($archivo['tmp_name'] ?? '');
        if ($temporal === '' || ($exigirCargaHttp && !is_uploaded_file($temporal)) || !is_file($temporal)) {
            return ['archivo' => 'El archivo recibido no es una carga válida.'];
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporal) ?: '';
        $permitidos = [
            'pdf' => ['application/pdf'],
            'zip' => ['application/zip', 'application/x-zip-compressed', 'application/x-zip'],
        ];
        if (!in_array($mime, $permitidos[$extension], true)) {
            return ['archivo' => 'El contenido del archivo no corresponde a un PDF o un ZIP.'];
        }

        return [];
    }

    /**
     * Descripcion: Almacena el archivo fuera del directorio público.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function guardar(int $ofertaId, array $archivo): string
    {
        $extension = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
        $base = self::nombreVisible((string) $archivo['name']);
        $directorio = dirname(__DIR__, 2) . '/storage/ofertas/' . $ofertaId;
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new \RuntimeException('No se pudo crear la carpeta de documentos.');
        }

        $relativa = 'storage/ofertas/' . $ofertaId . '/' . bin2hex(random_bytes(8)) . '_' . $base;
        $destino = dirname(__DIR__, 2) . '/' . $relativa;
        if (!move_uploaded_file((string) $archivo['tmp_name'], $destino)) {
            throw new \RuntimeException('No se pudo almacenar el archivo.');
        }

        return $relativa;
    }

    /**
     * Descripcion: Resuelve la ruta del archivo e impide salir de la carpeta de documentos.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function rutaAbsolutaSegura(string $relativa): ?string
    {
        $raiz = realpath(dirname(__DIR__, 2) . '/storage/ofertas');
        $absoluta = realpath(dirname(__DIR__, 2) . '/' . $relativa);
        if ($raiz === false || $absoluta === false || !is_file($absoluta)) {
            return null;
        }

        if (!str_starts_with($absoluta, $raiz . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $absoluta;
    }

    /**
     * Descripcion: Obtiene el nombre de descarga sin el prefijo interno.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function nombreVisible(string $nombreORuta): string
    {
        $base = basename(str_replace('\\', '/', $nombreORuta));
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', $base) ?? 'documento';
        if (preg_match('/^[a-f0-9]{16}_(.+)$/', $base, $coincidencias)) {
            return $coincidencias[1];
        }

        return $base === '' ? 'documento' : $base;
    }
}
