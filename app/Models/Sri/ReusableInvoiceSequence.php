<?php

namespace App\Models\Sri;

use App\Models\Sales\QueuedSale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReusableInvoiceSequence extends Model
{
    use HasFactory;

    protected $table = 'reusable_invoice_sequences';

    protected $fillable = [
        'sequence',
        'num_factura',
        'released_from_queue_id',
        'reused_at',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'reused_at' => 'datetime',
    ];

    public function releasedFromQueue()
    {
        return $this->belongsTo(QueuedSale::class, 'released_from_queue_id');
    }
}
