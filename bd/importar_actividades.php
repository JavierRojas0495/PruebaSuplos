<?php

declare(strict_types=1);

use App\Models\Actividad;
use App\Models\Oferta;
use Illuminate\Database\Capsule\Manager as Capsule;
use PhpOffice\PhpSpreadsheet\IOFactory;

require dirname(__DIR__) . '/app/bootstrap.php';

ini_set('memory_limit', '1024M');

$ruta = $argv[1] ?? (dirname(__DIR__) . '/bd/fuentes/unspc.xlsx');
if (!is_file($ruta)) {
    $destino = dirname(__DIR__) . '/bd/fuentes';
    if (!is_dir($destino)) {
        mkdir($destino, 0775, true);
    }
    $ruta = $destino . '/unspc.xlsx';
    $url = 'https://a.storyblok.com/f/167454/x/8db69f44cd/unspcs-clasificador-de-bienes-y-servicios-de-naciones-unidas-en-espanol.xlsx';
    echo "Descargando maestra UNSPSC...\n";
    $contenido = file_get_contents($url);
    if ($contenido === false || file_put_contents($ruta, $contenido) === false) {
        fwrite(STDERR, "No se pudo descargar la maestra. Pase la ruta del xlsx como argumento.\n");
        exit(1);
    }
}

if (Oferta::query()->exists()) {
    fwrite(STDERR, "Ya existen ofertas. No se recarga la maestra para no romper actividades en uso.\n");
    exit(1);
}

echo "Leyendo {$ruta}\n";
$lector = IOFactory::createReader('Xlsx');
$lector->setReadDataOnly(true);
$libro = $lector->load($ruta);
$hoja = $libro->getSheetByName('Clasificador') ?? $libro->getActiveSheet();
$filas = $hoja->toArray(null, true, true, false);
$libro->disconnectWorksheets();

Actividad::query()->delete();

$lote = [];
$total = 0;

foreach ($filas as $indice => $fila) {
    if ($indice < 6) {
        continue;
    }
    $segmento = $fila[0] ?? null;
    if ($segmento === null || $segmento === '') {
        continue;
    }

    $lote[] = [
        'codigo_segmento' => (int) $segmento,
        'segmento' => mb_substr(trim((string) ($fila[1] ?? '')), 0, 200),
        'codigo_familia' => (int) ($fila[2] ?? 0),
        'familia' => mb_substr(trim((string) ($fila[3] ?? '')), 0, 200),
        'codigo_clase' => (int) ($fila[4] ?? 0),
        'clase' => mb_substr(trim((string) ($fila[5] ?? '')), 0, 200),
        'codigo_producto' => (int) ($fila[6] ?? 0),
        'producto' => mb_substr(trim((string) ($fila[7] ?? '')), 0, 200),
    ];

    if (count($lote) >= 500) {
        Capsule::table('actividades')->insert($lote);
        $total += count($lote);
        $lote = [];
        echo "Importadas {$total}\n";
    }
}

if ($lote !== []) {
    Capsule::table('actividades')->insert($lote);
    $total += count($lote);
}

echo "Listo. Actividades importadas: {$total}\n";
