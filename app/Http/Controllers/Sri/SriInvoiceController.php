<?php

namespace App\Http\Controllers\Sri;

use App\Http\Controllers\Controller;
use App\Services\Sri\SriInvoiceService;

class SriInvoiceController extends Controller
{
    public function __construct(private SriInvoiceService $service) {}

    public function generate(int $saleId)
    {
        $inv = $this->service->generateXmlForSale($saleId);

        return back()->with('success', 'XML generado correctamente. Clave: '.$inv->clave_acceso);
    }
}
