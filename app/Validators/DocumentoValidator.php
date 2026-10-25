<?php

declare(strict_types=1);

namespace App\Validators;

final class DocumentoValidator
{
    /**
     * Descripcion: Revisa que el título y la descripción del documento cumplan sus límites.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function validarTextos(string $titulo, string $descripcion): array
    {
        $errores = [];
        $titulo = trim($titulo);
        $descripcion = trim($descripcion);

        if ($titulo === '') {
            $errores['titulo'] = 'El título del documento es obligatorio.';
        } elseif (mb_strlen($titulo) > 100) {
            $errores['titulo'] = 'El título del documento no puede superar 100 caracteres.';
        }

        if ($descripcion === '') {
            $errores['descripcion'] = 'La descripción del documento es obligatoria.';
        } elseif (mb_strlen($descripcion) > 200) {
            $errores['descripcion'] = 'La descripción del documento no puede superar 200 caracteres.';
        }

        return $errores;
    }
}
