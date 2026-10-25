<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $rutas = [];

    /**
     * Descripcion: Registra una ruta GET.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function get(string $patron, callable $accion): void
    {
        $this->rutas[] = ['GET', $patron, $accion];
    }

    /**
     * Descripcion: Registra una ruta POST.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function post(string $patron, callable $accion): void
    {
        $this->rutas[] = ['POST', $patron, $accion];
    }

    /**
     * Descripcion: Ejecuta la ruta que coincide con el método y la URI.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function despachar(string $metodo, string $uri): void
    {
        $metodo = strtoupper($metodo);
        if ($metodo === 'OPTIONS') {
            Response::cors();
            http_response_code(204);
            return;
        }

        foreach ($this->rutas as [$metodoRuta, $patron, $accion]) {
            if ($metodoRuta !== $metodo) {
                continue;
            }

            $parametros = $this->coincidir($patron, $uri);
            if ($parametros === null) {
                continue;
            }

            $this->invocar($accion, $parametros);
            return;
        }

        Response::json(['message' => 'Ruta no encontrada.'], 404);
    }

    /**
     * Descripcion: Llama la acción con la petición y, si la ruta los trae, sus parámetros.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function invocar(callable $accion, array $parametros): void
    {
        $reflejo = new \ReflectionFunction(\Closure::fromCallable($accion));
        if ($reflejo->getNumberOfParameters() >= 2) {
            $accion(new Request(), $parametros);
            return;
        }

        $accion(new Request());
    }

    /**
     * Descripcion: Compara el patrón de la ruta con la URI y extrae sus parámetros.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    private function coincidir(string $patron, string $uri): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $patron);
        if ($regex === null) {
            return null;
        }

        if (!preg_match('#^' . $regex . '$#', $uri, $coincidencias)) {
            return null;
        }

        $parametros = [];
        foreach ($coincidencias as $clave => $valor) {
            if (is_string($clave)) {
                $parametros[$clave] = $valor;
            }
        }

        return $parametros;
    }
}
