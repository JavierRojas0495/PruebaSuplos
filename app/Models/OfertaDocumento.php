<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfertaDocumento extends Model
{
    protected $table = 'ofertas_documentos';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['contenido'];

    /**
     * Descripcion: Relaciona el documento con su licitación.
     * Fecha: 24/10/26
     * Autor: Javier Rojas
     */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(Oferta::class, 'licitacion_id');
    }
}
