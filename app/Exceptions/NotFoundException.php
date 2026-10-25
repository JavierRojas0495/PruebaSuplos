<?php

declare(strict_types=1);

namespace App\Exceptions;

final class NotFoundException extends \RuntimeException
{
    /**
     * Descripcion: Crea la excepción de un recurso que no existe.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function __construct(string $mensaje = 'No se encontró el recurso.')
    {
        parent::__construct($mensaje);
    }
}
