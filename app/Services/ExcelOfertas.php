<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Models\Oferta;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExcelOfertas
{
    /**
     * Descripcion: Genera y envía el Excel con las licitaciones recibidas.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function descargar(array $ofertas): void
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Ofertas');
        $hoja->fromArray([
            'Consecutivo',
            'Objeto',
            'Descripción',
            'Fecha de inicio',
            'Fecha de cierre',
            'Estado',
        ]);

        $fila = 2;
        foreach ($ofertas as $oferta) {
            $hoja->fromArray([
                $oferta->consecutivo,
                $oferta->objeto,
                $oferta->descripcion,
                substr((string) $oferta->fecha_inicio, 0, 10),
                substr((string) $oferta->fecha_cierre, 0, 10),
                EstadoOferta::calcular(
                    (string) $oferta->fecha_inicio,
                    (string) $oferta->hora_inicio,
                    (string) $oferta->fecha_cierre,
                    (string) $oferta->hora_cierre
                ),
            ], null, 'A' . $fila);
            $fila++;
        }

        foreach (range('A', 'F') as $columna) {
            $hoja->getColumnDimension($columna)->setAutoSize(true);
        }

        Response::cors();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ofertas.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($libro))->save('php://output');
    }
}
