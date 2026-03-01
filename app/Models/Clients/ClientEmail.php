<?php

namespace App\Models\Clients;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'email',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
