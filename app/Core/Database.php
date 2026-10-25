<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

final class Database
{
    private static bool $iniciado = false;

    /**
     * Descripcion: Inicia Eloquent con la conexión MySQL definida en el entorno.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public static function boot(): void
    {
        if (self::$iniciado) {
            return;
        }

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', '3306'),
            'database' => Env::get('DB_DATABASE', 'suplos_ofertas'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        self::$iniciado = true;
    }
}
