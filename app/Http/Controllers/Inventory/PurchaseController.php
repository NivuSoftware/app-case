<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\Inventory\PurchaseService;
use App\Services\Inventory\SupplierService;
use App\Models\Product\Product;
use App\Models\Store\Bodega;
use App\Models\Store\Percha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Inventory\Supplier;

class PurchaseController extends Controller
{
    protected PurchaseService $service;
    protected SupplierService $supplierService;

    public function __construct(PurchaseService $service, SupplierService $supplierService)
    {
        $this->service = $service;
        $this->supplierService = $supplierService;
    }

    public function viewIndex()
    {
        return view('inventario.compras.index');
    }

    public function viewCreate()
    {
        $suppliers = Supplier::orderBy('nombre')->get();
        $products  = Product::orderBy('nombre')->get();
        $bodegas   = Bodega::orderBy('nombre')->get();
        $perchas   = Percha::orderBy('codigo')->get();

        return view('inventario.compras.create', compact(
            'suppliers', 'products', 'bodegas', 'perchas'
        ));
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->listAll());
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->getById($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'fecha_compra'   => 'required|date',
            'num_factura'    => 'nullable|string|max:100',
            'iva_porcentaje' => 'nullable|numeric|min:0',
            'observaciones'  => 'nullable|string|max:500',
            'descripcion'    => 'nullable|string|max:500',

            'metodo_pago'    => 'nullable|string|max:50',
            'pago_inicial'   => 'nullable|numeric|min:0',
            'referencia'     => 'nullable|string|max:100',

            'items'                  => 'required|array|min:1',
            'items.*.producto_id'    => 'required|exists:products,id',
            'items.*.bodega_id'      => 'required|exists:bodegas,id',
            'items.*.percha_id'      => 'nullable|exists:perchas,id',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.costo_unitario' => 'required|numeric|min:0',
            'items.*.grava_iva'      => 'nullable|boolean',
        ]);

        // Si viene "descripcion" y NO viene "observaciones",
        // usamos la descripción como observaciones de la compra
        $descripcion = $validated['descripcion'] ?? null;
        if ($descripcion && empty($validated['observaciones'])) {
            $validated['observaciones'] = $descripcion;
        }

        $payload = [
            'supplier_id'    => $validated['supplier_id'],
            'fecha_compra'   => $validated['fecha_compra'],
            'num_factura'    => $validated['num_factura'] ?? null,
            'iva_porcentaje' => $validated['iva_porcentaje'] ?? null,
            'observaciones'  => $validated['observaciones'] ?? null,
            'metodo_pago'    => $validated['metodo_pago'] ?? null,
            'pago_inicial'   => $validated['pago_inicial'] ?? null,
            'referencia'     => $validated['referencia'] ?? null,
            'items'          => $validated['items'],
        ];

        $purchase = $this->service->registerPurchase($payload);

        return response()->json($purchase, 201);
    }


    public function addPayment(Request $request, int $purchaseId): JsonResponse
    {
        $data = $request->validate([
            'fecha_pago'    => 'required|date',
            'monto'         => 'required|numeric|min:0.01',
            'metodo'        => 'required|string|max:50',
            'referencia'    => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $purchase = $this->service->registerPayment($purchaseId, $data);

        return response()->json($purchase);
    }
}
