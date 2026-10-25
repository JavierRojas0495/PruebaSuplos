<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Services\ArchivoService;
use App\Services\EstadoOferta;
use App\Validators\DocumentoValidator;
use App\Validators\OfertaValidator;

Env::load(dirname(__DIR__) . '/.env');

$fallos = 0;

/**
 * Descripcion: Registra si una comprobación pasó o falló.
 * Fecha: 24/10/26
 * Autor: Javier Rojas
 */
function esperar(bool $condicion, string $mensaje): void
{
    global $fallos;
    if ($condicion) {
        echo "OK  {$mensaje}\n";
        return;
    }
    $fallos++;
    echo "FALLO  {$mensaje}\n";
}

$base = [
    'objeto' => 'Compra de equipos',
    'descripcion' => 'Alcance de la licitación de equipos de cómputo.',
    'moneda' => 'COP',
    'presupuesto' => '1500000.50',
    'actividad_id' => '10',
    'fecha_inicio' => '2026-11-01',
    'hora_inicio' => '08:00',
    'fecha_cierre' => '2026-11-01',
    'hora_cierre' => '18:00',
];

esperar(OfertaValidator::validar($base) === [], 'oferta válida no produce errores');

$vacia = $base;
$vacia['objeto'] = '   ';
$vacia['presupuesto'] = '0';
$vacia['moneda'] = 'MXN';
$vacia['fecha_cierre'] = '2026-10-01';
$errores = OfertaValidator::validar($vacia);
esperar(isset($errores['objeto'], $errores['presupuesto'], $errores['moneda'], $errores['fecha_cierre']), 'rechaza vacío, cero, moneda y fecha');

$horas = $base;
$horas['hora_cierre'] = '08:00';
esperar(isset(OfertaValidator::validar($horas)['hora_cierre']), 'rechaza cierre igual al inicio el mismo día');

$decimales = $base;
$decimales['presupuesto'] = '10.999';
esperar(isset(OfertaValidator::validar($decimales)['presupuesto']), 'rechaza más de dos decimales');

$largo = $base;
$largo['objeto'] = str_repeat('a', 151);
esperar(isset(OfertaValidator::validar($largo)['objeto']), 'rechaza objeto de 151 caracteres');

$normal = OfertaValidator::normalizar($base);
esperar($normal['presupuesto'] === '1500000.50' && $normal['hora_inicio'] === '08:00:00', 'normaliza presupuesto y hora');

esperar(
    EstadoOferta::calcular('2026-11-01', '08:00:00', '2026-11-02', '18:00:00', new DateTimeImmutable('2026-10-01 00:00:00')) === 'Programada',
    'estado programada'
);
esperar(
    EstadoOferta::calcular('2026-11-01', '08:00:00', '2026-11-02', '18:00:00', new DateTimeImmutable('2026-11-01 12:00:00')) === 'En curso',
    'estado en curso'
);
esperar(
    EstadoOferta::calcular('2026-11-01', '08:00:00', '2026-11-02', '18:00:00', new DateTimeImmutable('2026-11-03 00:00:00')) === 'Cerrada',
    'estado cerrada'
);

esperar(DocumentoValidator::validarTextos('  ', 'Pliego') !== [], 'título de documento vacío');
esperar(DocumentoValidator::validarTextos('Pliego', str_repeat('b', 201)) !== [], 'descripción de documento larga');

$pdf = tempnam(sys_get_temp_dir(), 'pdf');
file_put_contents($pdf, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
$falso = tempnam(sys_get_temp_dir(), 'txt');
file_put_contents($falso, 'esto no es un pdf');

esperar(ArchivoService::validar([
    'name' => 'pliego.pdf',
    'tmp_name' => $pdf,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($pdf),
], false) === [], 'acepta un PDF real');

esperar(isset(ArchivoService::validar([
    'name' => 'pliego.pdf',
    'tmp_name' => $falso,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($falso),
], false)['archivo']), 'rechaza un PDF cuyo contenido no es PDF');

esperar(isset(ArchivoService::validar([
    'name' => 'pliego.exe',
    'tmp_name' => $pdf,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($pdf),
], false)['archivo']), 'rechaza una extensión distinta de PDF o ZIP');

unlink($pdf);
unlink($falso);

if ($fallos > 0) {
    fwrite(STDERR, "{$fallos} comprobaciones fallaron.\n");
    exit(1);
}

echo "Todas las comprobaciones pasaron.\n";
