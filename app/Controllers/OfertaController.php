<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ExcelOfertas;
use App\Services\OfertaService;

final class OfertaController
{
    /**
     * Descripcion: Prepara el servicio de ofertas que atiende las acciones del controlador.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function __construct(private OfertaService $ofertas = new OfertaService())
    {
    }

    /**
     * Descripcion: Lista las licitaciones paginadas según los filtros de la consulta.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function index(Request $solicitud): void
    {
        $pagina = max(1, (int) $solicitud->consulta('page', '1'));
        $porPagina = (int) $solicitud->consulta('per_page', '10');
        if ($porPagina < 1 || $porPagina > 100) {
            $porPagina = 10;
        }

        Response::json($this->ofertas->listar($this->filtros($solicitud), $pagina, $porPagina));
    }

    /**
     * Descripcion: Genera el Excel del listado con los mismos filtros y sin paginar.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function exportar(Request $solicitud): void
    {
        ExcelOfertas::descargar($this->ofertas->paraExcel($this->filtros($solicitud)));
    }

    /**
     * Descripcion: Devuelve el detalle de una licitación.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function mostrar(Request $solicitud, array $parametros): void
    {
        $oferta = $this->ofertas->obtener($this->id($parametros));
        Response::json(['data' => $this->ofertas->detalle($oferta)]);
    }

    /**
     * Descripcion: Crea una licitación y responde con su detalle.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function crear(Request $solicitud): void
    {
        $oferta = $this->ofertas->crear($solicitud->json());
        Response::json(['data' => $this->ofertas->detalle($oferta)], 201);
    }

    /**
     * Descripcion: Actualiza la licitación y guarda los documentos nuevos enviados en el formulario.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function actualizar(Request $solicitud, array $parametros): void
    {
        $oferta = $this->ofertas->actualizar(
            $this->id($parametros),
            $solicitud->formularioOferta(),
            $this->documentosNuevos()
        );
        Response::json(['data' => $this->ofertas->detalle($oferta)]);
    }

    /**
     * Descripcion: Toma consecutivo, objeto y descripción desde la consulta.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function filtros(Request $solicitud): array
    {
        return [
            'consecutivo' => $solicitud->consulta('consecutivo'),
            'objeto' => $solicitud->consulta('objeto'),
            'descripcion' => $solicitud->consulta('descripcion'),
        ];
    }

    /**
     * Descripcion: Obtiene el id numérico que viene en la ruta.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function id(array $parametros): int
    {
        return (int) ($parametros['id'] ?? 0);
    }

    /**
     * Descripcion: Arma los documentos recibidos en el formulario de edición.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function documentosNuevos(): array
    {
        $titulos = $this->listaPost('titulo_documento');
        $descripciones = $this->listaPost('descripcion_documento');
        $archivos = $this->listaArchivos($_FILES['archivo'] ?? null);
        $cantidad = max(count($titulos), count($descripciones), count($archivos));
        $documentos = [];

        for ($i = 0; $i < $cantidad; $i++) {
            $documentos[] = [
                'titulo' => trim((string) ($titulos[$i] ?? '')),
                'descripcion' => trim((string) ($descripciones[$i] ?? '')),
                'archivo' => $archivos[$i] ?? ['error' => UPLOAD_ERR_NO_FILE],
            ];
        }

        return $documentos;
    }

    /**
     * Descripcion: Normaliza un campo de formulario que puede llegar solo o como lista.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function listaPost(string $clave): array
    {
        $valor = $_POST[$clave] ?? [];
        if (!is_array($valor)) {
            return [trim((string) $valor)];
        }

        return array_values($valor);
    }

    /**
     * Descripcion: Normaliza la estructura de archivos subidos por PHP.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function listaArchivos(?array $archivos): array
    {
        if ($archivos === null || !isset($archivos['name'])) {
            return [];
        }
        if (!is_array($archivos['name'])) {
            return [$archivos];
        }

        $lista = [];
        foreach ($archivos['name'] as $indice => $nombre) {
            if ($nombre === '' && (int) ($archivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $lista[] = [
                'name' => $nombre,
                'type' => $archivos['type'][$indice] ?? '',
                'tmp_name' => $archivos['tmp_name'][$indice] ?? '',
                'error' => $archivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE,
                'size' => $archivos['size'][$indice] ?? 0,
            ];
        }

        return $lista;
    }
}
