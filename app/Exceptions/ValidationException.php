<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ValidationException extends \RuntimeException
{
    /**
     * Descripcion: Crea la excepción con los errores de validación.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function __construct(private array $errores)
    {
        parent::__construct('Hay errores de validación.');
    }

    /**
     * Descripcion: Devuelve los errores de validación agrupados por campo.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function errores(): array
    {
        return $this->errores;
    }
}
