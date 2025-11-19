<?php

namespace App\Repositories\Store;

use App\Models\Store\Percha;

class PerchaRepository
{
    public function all()
    {
        return Percha::orderBy('id', 'desc')->get();
    }

    public function find($id)
    {
        return Percha::findOrFail($id);
    }

    public function create(array $data)
    {
        return Percha::create($data);
    }

    public function update(Percha $percha, array $data)
    {
        $percha->update($data);
        return $percha;
    }

    public function delete(Percha $percha)
    {
        return $percha->delete();
    }

    public function getByBodega($bodegaId)
    {
        return Percha::where('bodega_id', $bodegaId)
                     ->orderBy('codigo')
                     ->get();
    }
}
