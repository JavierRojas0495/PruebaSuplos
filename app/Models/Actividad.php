<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actividad extends Model
{
    protected $table = 'actividades';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Descripcion: Relaciona la actividad con las ofertas que la usan.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class, 'actividad_id');
    }
}
