<?php

namespace App\Repositories\Product;

use App\Models\Product\Product;

class ProductRepository
{
    public function all(
        bool $onlyActive = false,
        bool $withPrice = true,
        bool $withTierPrices = true
    ) {
        $query = Product::query()->orderBy('nombre', 'asc');

        $with = [];

        if ($withPrice) $with[] = 'price';
        if ($withTierPrices) $with[] = 'productPrices'; // ✅ ya existe

        if (!empty($with)) $query->with($with);

        if ($onlyActive) $query->where('estado', true);

        return $query->get();
    }

    public function find(
        $id,
        bool $withPrice = true,
        bool $withTierPrices = true
    ) {
        $query = Product::query();

        $with = [];
        if ($withPrice) $with[] = 'price';
        if ($withTierPrices) $with[] = 'productPrices';

        if (!empty($with)) $query->with($with);

        return $query->findOrFail($id);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data)
    {
        $product->update($data);

        return $product->refresh()->load(['price', 'productPrices']);
    }

    public function delete(Product $product)
    {
        return $product->delete();
    }
}
