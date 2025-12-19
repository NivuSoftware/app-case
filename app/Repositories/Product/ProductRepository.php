<?php

namespace App\Repositories\Product;

use App\Models\Product\Product;

class ProductRepository
{
    /**
     * Lista productos con su precio.
     *
     * @param  bool  $onlyActive  Si true, solo productos con estado = 1
     */
    public function all(bool $onlyActive = false)
    {
        $query = Product::with('price')
            ->orderBy('nombre', 'asc');

        if ($onlyActive) {
            $query->where('estado', true);
        }

        return $query->get();
    }

    /**
     * Busca un producto por ID, incluyendo su price.
     */
    public function find($id)
    {
        return Product::with('price')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data)
    {
        $product->update($data);

        return $product->refresh()->load('price');
    }

    public function delete(Product $product)
    {
        return $product->delete();
    }
}
