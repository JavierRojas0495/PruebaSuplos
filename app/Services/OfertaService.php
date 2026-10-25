<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Actividad;
use App\Models\Oferta;
use App\Models\OfertaDocumento;
use App\Validators\DocumentoValidator;
use App\Validators\OfertaValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Capsule\Manager as Capsule;

final class OfertaService
{
    /**
     * Descripcion: Valida y guarda una licitación nueva con el consecutivo que asigna el servidor.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function crear(array $datos): Oferta
    {
        $this->exigirOfertaValida($datos);

        $limpio = OfertaValidator::normalizar($datos);

        return $this->reintentar(function () use ($limpio) {
            $ahora = date('Y-m-d H:i:s');
            $oferta = new Oferta();
            $oferta->fill($this->atributos($limpio, $ahora));
            $oferta->consecutivo = ConsecutivoService::siguiente();
            $oferta->creado_en = $ahora;
            $oferta->save();

            return $oferta->load(['actividad', 'documentos']);
        });
    }

    /**
     * Descripcion: Valida la edición, exige al menos un documento y guarda los archivos nuevos.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function actualizar(int $id, array $datos, array $documentos): Oferta
    {
        $oferta = Oferta::query()->withCount('documentos')->find($id);
        if (!$oferta instanceof Oferta) {
            throw new NotFoundException('La licitación no existe.');
        }

        $errores = $this->erroresOferta($datos);
        if ($oferta->documentos_count + count($documentos) < 1) {
            $errores['documentos'] = 'Debe registrar al menos un documento.';
        }

        foreach ($documentos as $indice => $documento) {
            foreach (DocumentoValidator::validarTextos($documento['titulo'], $documento['descripcion']) as $campo => $mensaje) {
                $errores['documentos.' . $indice . '.' . $campo] = $mensaje;
            }
            foreach (ArchivoService::validar($documento['archivo']) as $campo => $mensaje) {
                $errores['documentos.' . $indice . '.' . $campo] = $mensaje;
            }
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        $limpio = OfertaValidator::normalizar($datos);

        return $this->reintentar(function () use ($oferta, $limpio, $documentos) {
            $ahora = date('Y-m-d H:i:s');
            $oferta->fill($this->atributos($limpio, $ahora));
            $oferta->save();

            foreach ($documentos as $documento) {
                OfertaDocumento::query()->create([
                    'licitacion_id' => $oferta->id,
                    'titulo' => trim($documento['titulo']),
                    'descripcion' => trim($documento['descripcion']),
                    'archivo' => ArchivoService::guardar((int) $oferta->id, $documento['archivo']),
                    'creado_en' => $ahora,
                ]);
            }

            return $oferta->refresh()->load(['actividad', 'documentos']);
        });
    }

    /**
     * Descripcion: Busca una licitación con su actividad y sus documentos.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function obtener(int $id): Oferta
    {
        $oferta = Oferta::query()->with(['actividad', 'documentos'])->find($id);
        if (!$oferta instanceof Oferta) {
            throw new NotFoundException('La licitación no existe.');
        }

        return $oferta;
    }

    /**
     * Descripcion: Devuelve la página pedida del listado filtrado.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function listar(array $filtros, int $pagina, int $porPagina): array
    {
        $consulta = $this->filtrar($filtros)->orderByDesc('id');
        $total = (clone $consulta)->count();
        $items = $consulta->forPage($pagina, $porPagina)->get();

        return [
            'data' => array_map(fn (Oferta $oferta) => $this->resumen($oferta), $items->all()),
            'meta' => [
                'page' => $pagina,
                'per_page' => $porPagina,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $porPagina)),
            ],
        ];
    }

    /**
     * Descripcion: Devuelve todas las licitaciones del filtro, sin paginar.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function paraExcel(array $filtros): array
    {
        return $this->filtrar($filtros)->orderByDesc('id')->get()->all();
    }

    /**
     * Descripcion: Aplica los filtros de consecutivo, objeto y descripción.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function filtrar(array $filtros): Builder
    {
        $consulta = Oferta::query();
        foreach (['consecutivo', 'objeto', 'descripcion'] as $campo) {
            $valor = trim((string) ($filtros[$campo] ?? ''));
            if ($valor === '') {
                continue;
            }
            $consulta->where($campo, 'like', $this->contiene($valor));
        }

        return $consulta;
    }

    /**
     * Descripcion: Interrumpe el guardado si la oferta no cumple las reglas.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function exigirOfertaValida(array $datos): void
    {
        $errores = $this->erroresOferta($datos);
        if ($errores !== []) {
            throw new ValidationException($errores);
        }
    }

    /**
     * Descripcion: Reúne los errores de formato y comprueba que la actividad exista.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function erroresOferta(array $datos): array
    {
        $errores = OfertaValidator::validar($datos);
        if (isset($errores['actividad_id'])) {
            return $errores;
        }

        $id = (int) $datos['actividad_id'];
        if (!Actividad::query()->whereKey($id)->exists()) {
            $errores['actividad_id'] = 'La actividad seleccionada no existe.';
        }

        return $errores;
    }

    /**
     * Descripcion: Prepara las columnas de la oferta, incluido el estado calculado.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function atributos(array $limpio, string $actualizadoEn): array
    {
        return [
            'objeto' => $limpio['objeto'],
            'descripcion' => $limpio['descripcion'],
            'moneda' => $limpio['moneda'],
            'presupuesto' => $limpio['presupuesto'],
            'actividad_id' => $limpio['actividad_id'],
            'fecha_inicio' => $limpio['fecha_inicio'],
            'hora_inicio' => $limpio['hora_inicio'],
            'fecha_cierre' => $limpio['fecha_cierre'],
            'hora_cierre' => $limpio['hora_cierre'],
            'estado' => EstadoOferta::calcular(
                (string) $limpio['fecha_inicio'],
                (string) $limpio['hora_inicio'],
                (string) $limpio['fecha_cierre'],
                (string) $limpio['hora_cierre']
            ),
            'actualizado_en' => $actualizadoEn,
        ];
    }

    /**
     * Descripcion: Arma las columnas que muestra el listado.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function resumen(Oferta $oferta): array
    {
        return [
            'id' => (int) $oferta->id,
            'consecutivo' => $oferta->consecutivo,
            'objeto' => $oferta->objeto,
            'descripcion' => $oferta->descripcion,
            'fecha_inicio' => substr((string) $oferta->fecha_inicio, 0, 10),
            'fecha_cierre' => substr((string) $oferta->fecha_cierre, 0, 10),
            'estado' => EstadoOferta::calcular(
                (string) $oferta->fecha_inicio,
                (string) $oferta->hora_inicio,
                (string) $oferta->fecha_cierre,
                (string) $oferta->hora_cierre
            ),
        ];
    }

    /**
     * Descripcion: Arma la ficha completa de la licitación.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function detalle(Oferta $oferta): array
    {
        $actividad = $oferta->actividad;

        return array_merge($this->resumen($oferta), [
            'moneda' => $oferta->moneda,
            'presupuesto' => number_format((float) $oferta->presupuesto, 2, '.', ''),
            'hora_inicio' => substr((string) $oferta->hora_inicio, 0, 5),
            'hora_cierre' => substr((string) $oferta->hora_cierre, 0, 5),
            'creado_en' => (string) $oferta->creado_en,
            'actualizado_en' => (string) $oferta->actualizado_en,
            'actividad' => $actividad === null ? null : [
                'id' => (int) $actividad->id,
                'codigo_segmento' => (int) $actividad->codigo_segmento,
                'segmento' => $actividad->segmento,
                'codigo_familia' => (int) $actividad->codigo_familia,
                'familia' => $actividad->familia,
                'codigo_clase' => (int) $actividad->codigo_clase,
                'clase' => $actividad->clase,
                'codigo_producto' => (int) $actividad->codigo_producto,
                'producto' => $actividad->producto,
            ],
            'documentos' => $oferta->documentos
                ->sortBy('id')
                ->values()
                ->map(fn (OfertaDocumento $documento) => [
                    'id' => (int) $documento->id,
                    'titulo' => $documento->titulo,
                    'descripcion' => $documento->descripcion,
                    'nombre' => ArchivoService::nombreVisible((string) $documento->archivo),
                    'creado_en' => (string) $documento->creado_en,
                    'url' => '/api/documentos/' . $documento->id,
                ])
                ->all(),
        ]);
    }

    /**
     * Descripcion: Prepara un patrón LIKE seguro con el texto buscado.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function contiene(string $valor): string
    {
        $escapado = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);

        return '%' . $escapado . '%';
    }

    /**
     * Descripcion: Guarda dentro de una transacción y reintenta si el consecutivo se repite.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function reintentar(callable $accion): mixed
    {
        $ultimo = null;
        for ($intento = 0; $intento < 3; $intento++) {
            try {
                return Capsule::connection()->transaction($accion);
            } catch (QueryException $excepcion) {
                $ultimo = $excepcion;
                $codigo = (string) $excepcion->getCode();
                if (!in_array($codigo, ['23000', '1062'], true) || $intento === 2) {
                    throw $excepcion;
                }
            }
        }

        throw $ultimo ?? new \RuntimeException('No se pudo guardar la licitación.');
    }
}
