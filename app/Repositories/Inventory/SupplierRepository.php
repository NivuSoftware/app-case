<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\Supplier;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository
{
    public function all(): Collection
    {
        return Supplier::orderBy('nombre')->get();
    }

    public function find(int $id): Supplier
    {
        return Supplier::findOrFail($id);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier;
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }
}
