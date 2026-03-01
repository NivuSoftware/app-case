<?php

namespace App\Models\Clients;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_identificacion',
        'identificacion',
        'business',
        'tipo',       // natural | juridico
        'telefono',
        'direccion',
        'ciudad',
        'estado',     // activo | inactivo
    ];

    /**
     * Relación: un cliente tiene muchos correos.
     */
    public function emails()
    {
        return $this->hasMany(ClientEmail::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }


    public function scopeByIdentificacion($query, string $tipoIdentificacion, string $identificacion)
    {
        return $query->where('tipo_identificacion', $tipoIdentificacion)
                     ->where('identificacion', $identificacion);
    }
}
