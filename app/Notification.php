<?php

namespace easyCRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cliente_id', 'cliente_seguimiento_id', 'estado'
    ];

    public $timestamps = false;

    protected $dates = ['deleted_at'];

    function clientes()
    {
        return $this->belongsTo('\easyCRM\Cliente', 'cliente_id');
    }

    function cliente_seguimientos()
    {
        return $this->belongsTo('\easyCRM\ClienteSeguimiento', 'cliente_seguimiento_id');
    }

}
