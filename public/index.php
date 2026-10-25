<?php

declare(strict_types=1);

use App\Controllers\ActividadController;
use App\Controllers\DocumentoController;
use App\Controllers\OfertaController;
use App\Core\Response;
use App\Core\Router;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use Illuminate\Database\Capsule\Manager as Capsule;

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$archivo = __DIR__ . $uri;
if ($uri !== '/' && is_file($archivo)) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';

if ($uri === '/api/salud') {
    try {
        Capsule::connection()->getPdo();
        Response::json(['ok' => true]);
    } catch (Throwable $excepcion) {
        $mensaje = App\Core\Env::debug()
            ? 'Sin conexión a la base de datos: ' . $excepcion->getMessage()
            : 'Sin conexión a la base de datos.';
        Response::json(['ok' => false, 'message' => $mensaje], 500);
    }
    return;
}

$router = new Router();
$ofertas = new OfertaController();
$actividades = new ActividadController();
$documentos = new DocumentoController();

$router->get('/api/actividades', [$actividades, 'index']);
$router->get('/api/ofertas', [$ofertas, 'index']);
$router->get('/api/ofertas/exportar', [$ofertas, 'exportar']);
$router->get('/api/ofertas/{id}', [$ofertas, 'mostrar']);
$router->post('/api/ofertas', [$ofertas, 'crear']);
$router->post('/api/ofertas/{id}', [$ofertas, 'actualizar']);
$router->get('/api/documentos/{id}', [$documentos, 'descargar']);

try {
    $router->despachar($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
} catch (ValidationException $excepcion) {
    Response::json([
        'message' => $excepcion->getMessage(),
        'errors' => $excepcion->errores(),
    ], 422);
} catch (NotFoundException $excepcion) {
    Response::json(['message' => $excepcion->getMessage()], 404);
} catch (Throwable $excepcion) {
    $cuerpo = ['message' => 'Error interno del servidor.'];
    if (App\Core\Env::debug()) {
        $cuerpo['detail'] = $excepcion->getMessage();
    }
    Response::json($cuerpo, 500);
}
