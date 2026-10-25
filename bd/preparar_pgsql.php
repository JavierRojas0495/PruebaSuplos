<?php

declare(strict_types=1);

/**
 * Descripcion: Crea las tablas en Supabase y carga la maestra si todavía está vacía.
 * Fecha: 25/10/26
 * Autor: Javier Rojas
 */

use Illuminate\Database\Capsule\Manager as Capsule;

require dirname(__DIR__) . '/app/bootstrap.php';

if ((App\Core\Env::get('DB_CONNECTION', 'mysql') ?? 'mysql') !== 'pgsql') {
    echo "DB_CONNECTION no es pgsql. No se modifica la base.\n";
    exit(0);
}

$conexion = Capsule::connection();
try {
    $conexion->unprepared('SET statement_timeout = 0');
} catch (Throwable) {
}

if ($conexion->selectOne("SELECT to_regclass('public.actividades') AS tabla")->tabla === null) {
    ejecutarArchivo(dirname(__DIR__) . '/bd/schema.pgsql.sql');
    echo "Tablas creadas.\n";
}

$total = (int) $conexion->table('actividades')->count();
if ($total >= 49022) {
    echo "La maestra ya está cargada ({$total}).\n";
    exit(0);
}
if ($total > 0) {
    fwrite(STDERR, "La tabla actividades tiene {$total} filas y no está completa. No se reinserta.\n");
    exit(1);
}

$origen = fopen(dirname(__DIR__) . '/bd/actividades.sql', 'rb');
if ($origen === false) {
    fwrite(STDERR, "No se encontró bd/actividades.sql.\n");
    exit(1);
}

$insertadas = 0;
while (($linea = fgets($origen)) !== false) {
    $linea = trim($linea);
    if ($linea === '' || str_starts_with($linea, '--') || str_starts_with($linea, 'USE ')) {
        continue;
    }
    if (!str_starts_with($linea, 'INSERT')) {
        continue;
    }
    $linea = str_replace('INSERT INTO `actividades`', 'INSERT INTO actividades', $linea);
    $conexion->unprepared($linea);
    $insertadas++;
    echo "Bloque {$insertadas} cargado.\n";
}
fclose($origen);

$maximo = (int) $conexion->table('actividades')->max('id');
if ($maximo > 0) {
    $conexion->unprepared("SELECT setval(pg_get_serial_sequence('actividades', 'id'), {$maximo})");
}

echo 'Maestra lista: ' . $conexion->table('actividades')->count() . " actividades.\n";

/**
 * Descripcion: Ejecuta un archivo SQL separado por sentencias.
 * Fecha: 25/10/26
 * Autor: Javier Rojas
 */
function ejecutarArchivo(string $ruta): void
{
    $sql = file_get_contents($ruta);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer ' . $ruta);
    }
    $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $sentencia) {
        Capsule::connection()->unprepared($sentencia);
    }
}
