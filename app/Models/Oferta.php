<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Oferta extends Model
{
    protected $table = 'ofertas';

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * Descripcion: Relaciona la oferta con su actividad.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }

    /**
     * Descripcion: Relaciona la oferta con sus documentos.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(OfertaDocumento::class, 'licitacion_id')
            ->select(['id', 'licitacion_id', 'titulo', 'descripcion', 'archivo', 'creado_en']);
    }
}
