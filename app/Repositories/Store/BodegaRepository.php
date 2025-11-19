<?php

namespace App\Repositories\Store;

use App\Models\Store\Bodega;

class BodegaRepository
{
    public function all()
    {
        return Bodega::orderBy('id', 'desc')->get();
    }

    public function find($id)
    {
        return Bodega::findOrFail($id);
    }

    public function create(array $data)
    {
        return Bodega::create($data);
    }

    public function update(Bodega $bodega, array $data)
    {
        $bodega->update($data);
        return $bodega;
    }

    public function delete(Bodega $bodega)
    {
        return $bodega->delete();
    }
}
