<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductPriceService;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    protected $service;

    public function __construct(ProductPriceService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->getAll());
    }

    public function show($id)
    {
        return response()->json($this->service->getById($id));
    }

    public function showByProduct($productoId)
    {
        return response()->json($this->service->getByProduct($productoId));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'producto_id' => 'required|exists:products,id',

            'precio_unitario' => 'required|numeric|min:0',

            'precio_por_cantidad' => 'nullable|numeric|min:0',
            'cantidad_min' => 'nullable|integer|min:1',
            'cantidad_max' => 'nullable|integer|gte:cantidad_min',

            'precio_por_caja' => 'nullable|numeric|min:0',
            'unidades_por_caja' => 'nullable|integer|min:1',

            'moneda' => 'required|string|max:10',
        ]);

        return response()->json($this->service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'precio_unitario' => 'sometimes|numeric|min:0',

            'precio_por_cantidad' => 'nullable|numeric|min:0',
            'cantidad_min' => 'nullable|integer|min:1',
            'cantidad_max' => 'nullable|integer|gte:cantidad_min',

            'precio_por_caja' => 'nullable|numeric|min:0',
            'unidades_por_caja' => 'nullable|integer|min:1',

            'moneda' => 'sometimes|string|max:10',
        ]);

        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Precio eliminado correctamente']);
    }
}
