<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\QueuedSale;
use App\Services\Sales\QueuedSaleService;
use Illuminate\Http\Request;

class QueuedSalePrintController extends Controller
{
    public function __construct(private QueuedSaleService $service)
    {
    }

    public function ticket(int $id, Request $request)
    {
        /** @var QueuedSale|null $queue */
        $queue = QueuedSale::query()
            ->where('id', $id)
            ->where('user_id', (int) $request->user()->id)
            ->first();

        abort_if(!$queue, 404);

        $sale = $this->service->buildTicketSaleViewModel($queue);
        $auto = $request->boolean('autoprint', true);

        return view('sales.print.ticket', compact('sale', 'auto'));
    }
}
