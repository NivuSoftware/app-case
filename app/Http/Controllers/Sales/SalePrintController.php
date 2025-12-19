<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Repositories\Sales\SaleRepository;
use Illuminate\Http\Request;

class SalePrintController extends Controller
{
    public function __construct(private SaleRepository $sales) {}

    public function ticket(int $id, Request $request)
    {
        $sale = $this->sales->findById($id);
        abort_if(!$sale, 404);

        $auto = $request->boolean('autoprint', true);

        return view('sales.print.ticket', compact('sale', 'auto'));
    }
}
