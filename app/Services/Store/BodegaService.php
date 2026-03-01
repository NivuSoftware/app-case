<?php

namespace App\Services\Store;

use App\Repositories\Store\BodegaRepository;

class BodegaService
{
    protected $repo;

    public function __construct(BodegaRepository $repo)
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

    public function create(array $data)
    {
        return $this->repo->create($data);
    }

    public function update($id, array $data)
    {
        $bodega = $this->repo->find($id);
        return $this->repo->update($bodega, $data);
    }

    public function delete($id)
    {
        $bodega = $this->repo->find($id);
        return $this->repo->delete($bodega);
    }
}
