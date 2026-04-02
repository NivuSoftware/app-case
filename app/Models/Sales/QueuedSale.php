<?php

namespace App\Models\Sales;

use App\Models\Clients\Client;
use App\Models\Clients\ClientEmail;
use App\Models\Store\Bodega;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueuedSale extends Model
{
    use HasFactory;

    protected $table = 'queued_sales';

    protected $fillable = [
        'user_id',
        'caja_id',
        'bodega_id',
        'client_id',
        'client_email_id',
        'email_destino',
        'fecha_venta',
        'tipo_documento',
        'observaciones',
        'iva_enabled',
        'payload_json',
        'status',
        'duration_seconds',
        'remaining_seconds',
        'execute_at',
        'schedule_version',
        'reserved_num_factura',
        'reserved_sequence',
        'sale_id',
        'last_error',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'iva_enabled' => 'boolean',
        'payload_json' => 'array',
        'execute_at' => 'datetime',
        'duration_seconds' => 'integer',
        'remaining_seconds' => 'integer',
        'schedule_version' => 'integer',
        'reserved_sequence' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function clientEmail()
    {
        return $this->belongsTo(ClientEmail::class, 'client_email_id');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
