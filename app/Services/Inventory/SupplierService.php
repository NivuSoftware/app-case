<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Supplier;

class SupplierService
{
    public function getAll()
    {
        return Supplier::orderBy('nombre')->get();
    }

    public function getById(int $id): Supplier
    {
        return Supplier::findOrFail($id);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create([
            'nombre'    => $data['nombre'],
            'ruc'       => $data['ruc']       ?? null,
            'telefono'  => $data['telefono']  ?? null,
            'email'     => $data['email']     ?? null,
            'direccion' => $data['direccion'] ?? null,
            'contacto'  => $data['contacto']  ?? null,   
            'activo'    => $data['activo']    ?? true,
        ]);
    }

    public function update(int $id, array $data): Supplier
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->update([
            'nombre'    => $data['nombre']    ?? $supplier->nombre,
            'ruc'       => $data['ruc']       ?? $supplier->ruc,
            'telefono'  => $data['telefono']  ?? $supplier->telefono,
            'email'     => $data['email']     ?? $supplier->email,
            'direccion' => $data['direccion'] ?? $supplier->direccion,
            'contacto'  => $data['contacto']  ?? $supplier->contacto, 
            'activo'    => $data['activo']    ?? $supplier->activo,
        ]);

        return $supplier;
    }

    public function delete(int $id): void
    {
        Supplier::findOrFail($id)->delete();
    }
}
