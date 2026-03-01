<?php

namespace App\Services\Store;

use App\Repositories\Store\PerchaRepository;

class PerchaService
{
    protected $repo;

    public function __construct(PerchaRepository $repo)
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

    public function getByBodega($bodegaId)
    {
        return $this->repo->getByBodega($bodegaId);
    }

    public function create(array $data)
    {
        return $this->repo->create($data);
    }

    public function update($id, array $data)
    {
        $percha = $this->repo->find($id);
        return $this->repo->update($percha, $data);
    }

    public function delete($id)
    {
        $percha = $this->repo->find($id);
        return $this->repo->delete($percha);
    }
}
