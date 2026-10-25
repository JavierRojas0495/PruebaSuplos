<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

$raiz = dirname(__DIR__);
require $raiz . '/vendor/autoload.php';

Env::load($raiz . '/.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'America/Bogota') ?? 'America/Bogota');
Database::boot();
