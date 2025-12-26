<?php

namespace App\Repositories\Sri;

use App\Models\Sri\ElectronicInvoice;

class ElectronicInvoiceRepository
{
    public function findBySaleId(int $saleId): ?ElectronicInvoice
    {
        return ElectronicInvoice::query()
            ->where('sale_id', $saleId)
            ->first();
    }

    public function create(array $data): ElectronicInvoice
    {
        return ElectronicInvoice::create($data);
    }

    public function updateBySaleId(int $saleId, array $data): int
    {
        return ElectronicInvoice::query()
            ->where('sale_id', $saleId)
            ->update($data);
    }
}
