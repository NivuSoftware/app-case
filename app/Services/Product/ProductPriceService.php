<?php

namespace App\Services\Product;

use App\Repositories\Product\ProductPriceRepository;

class ProductPriceService
{
    protected $repo;

    public function __construct(ProductPriceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getAll()
    {
        return $this->repo->all();
    }

    public function getById($id)
    {
        return $this->repo->find($id);
    }

    public function getByProduct($productoId)
    {
        return $this->repo->findByProduct($productoId);
    }

    public function create(array $data)
    {
        if (empty($data['moneda'])) {
            $data['moneda'] = 'USD';
        }

        return $this->repo->create($data);
    }

    public function update($id, array $data)
    {
        $price = $this->repo->find($id);

        if (empty($data['moneda'])) {
            $data['moneda'] = $price->moneda ?? 'USD';
        }

        return $this->repo->update($price, $data);
    }

    public function delete($id)
    {
        $price = $this->repo->find($id);
        return $this->repo->delete($price);
    }
}
