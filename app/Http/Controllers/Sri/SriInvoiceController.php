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

    public function consultAuthorization(int $saleId)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['status' => 'FORBIDDEN', 'message' => 'Solo admin puede consultar manualmente.'], 403);
        }

        return response()->json($this->service->consultAuthorizationOnce($saleId));
    }

}
