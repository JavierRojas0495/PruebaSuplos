<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\OfertaDocumento;
use App\Services\ArchivoService;

final class DocumentoController
{
    /**
     * Descripcion: Entrega el archivo de un documento cuando existe y su ruta es segura.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function descargar(Request $solicitud, array $parametros): void
    {
        $id = (int) ($parametros['id'] ?? 0);
        $documento = OfertaDocumento::query()->find($id);
        if ($documento === null) {
            Response::json(['message' => 'El documento no existe.'], 404);
            return;
        }

        $ruta = ArchivoService::rutaAbsolutaSegura((string) $documento->archivo);
        if ($ruta === null) {
            Response::json(['message' => 'El archivo no está disponible.'], 404);
            return;
        }

        Response::archivo($ruta, ArchivoService::nombreVisible((string) $documento->archivo));
    }
}
