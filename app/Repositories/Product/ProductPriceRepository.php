<?php

namespace App\Repositories\Product;

use App\Models\Product\ProductPrice;

class ProductPriceRepository
{
    public function all()
    {
        return ProductPrice::orderBy('id', 'desc')->get();
    }

    public function find($id)
    {
        return ProductPrice::findOrFail($id);
    }

    public function findByProduct($productoId)
    {
        return ProductPrice::where('producto_id', $productoId)
            ->orderBy('id', 'desc')
            ->first();
    }


    public function create(array $data)
    {
        return ProductPrice::create($data);
    }

    public function update(ProductPrice $price, array $data)
    {
        $price->update($data);
        return $price;
    }

    public function delete(ProductPrice $price)
    {
        return $price->delete();
    }
}
