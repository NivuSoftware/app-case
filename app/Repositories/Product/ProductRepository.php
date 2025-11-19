<?php

namespace App\Repositories\Product;

use App\Models\Product\Product;

class ProductRepository
{
    public function all()
    {
        return Product::orderBy('id', 'desc')->get();
    }

    public function find($id)
    {
        return Product::findOrFail($id);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data)
    {
        $product->update($data);
        return $product;
    }

    public function delete(Product $product)
    {
        return $product->delete();
    }
}
