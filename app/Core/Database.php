<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

final class Database
{
    private static bool $iniciado = false;

    /**
     * Descripcion: Inicia Eloquent con MySQL local o PostgreSQL de Supabase, según DB_CONNECTION.
     * Fecha: 25/10/26
     * Autor: Javier Rojas
     */
    public static function boot(): void
    {
        if (self::$iniciado) {
            return;
        }

        $driver = Env::get('DB_CONNECTION', 'mysql') ?? 'mysql';
        if (!in_array($driver, ['mysql', 'pgsql'], true)) {
            $driver = 'mysql';
        }

        $conexion = [
            'driver' => $driver,
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', $driver === 'pgsql' ? '5432' : '3306'),
            'database' => Env::get('DB_DATABASE', $driver === 'pgsql' ? 'postgres' : 'suplos_ofertas'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'prefix' => '',
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
        ];

        if ($driver === 'mysql') {
            $conexion['collation'] = 'utf8mb4_unicode_ci';
        } else {
            $conexion['sslmode'] = Env::get('DB_SSLMODE', 'require');
        }

        $capsule = new Capsule();
        $capsule->addConnection($conexion);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        self::$iniciado = true;
    }
}
