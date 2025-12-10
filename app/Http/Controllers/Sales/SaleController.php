<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Store\Bodega;
use App\Models\Sales\PaymentMethod;


class SaleController extends Controller
{
    protected SaleService $service;

    public function __construct(SaleService $service)
    {
        $this->service = $service;
    }

    /*
    |--------------------------------------------------------------------------
    | VISTAS
    |--------------------------------------------------------------------------
    */

    // Vista principal del módulo de ventas / POS
    public function viewIndex()
    {
        $bodegas        = Bodega::all();
        $paymentMethods = PaymentMethod::where('activo', true)->get();

        return view('sales.index', [
            'bodegas'        => $bodegas,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ENDPOINTS JSON
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse
    {
        // Validación de la estructura que espera el Service
        $data = $request->validate([
            'client_id'      => 'nullable|exists:clients,id',
            'user_id'        => 'required|exists:users,id',
            'bodega_id'      => 'required|exists:bodegas,id',
            'fecha_venta'    => 'required|date',
            'tipo_documento' => 'nullable|string|max:20',
            'num_factura'    => 'nullable|string|max:50',
            'observaciones'  => 'nullable|string|max:500',

            'items'                  => 'required|array|min:1',
            'items.*.producto_id'    => 'required|exists:products,id',
            'items.*.descripcion'    => 'required|string|max:255',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.precio_unitario'=> 'required|numeric|min:0',
            'items.*.descuento'      => 'nullable|numeric|min:0',
            'items.*.percha_id'      => 'nullable|exists:perchas,id',

            'payment'                        => 'required|array',
            'payment.metodo'                 => 'required|string|max:20',
            'payment.payment_method_id'      => 'nullable|exists:payment_methods,id',
            'payment.monto_recibido'         => 'required|numeric|min:0',
            'payment.referencia'             => 'nullable|string|max:100',
            'payment.observaciones'          => 'nullable|string|max:500',
            'payment.fecha_pago'             => 'nullable|date',
        ]);

        $sale = $this->service->crearVenta($data);

        return response()->json([
            'message' => 'Venta registrada correctamente',
            'data'    => $sale,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $sale = $this->service->getById($id); // podemos agregar este método luego

        return response()->json($sale);
    }
}
