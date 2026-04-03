<?php

namespace App\Repositories\Sales;

use App\Models\Sales\QueuedSale;
use Illuminate\Database\Eloquent\Collection;

class QueuedSaleRepository
{
    public function create(array $data): QueuedSale
    {
        return QueuedSale::create($data);
    }

    public function findById(int $id): ?QueuedSale
    {
        return QueuedSale::query()->find($id);
    }

    public function findOwnedById(int $id, int $userId): ?QueuedSale
    {
        return QueuedSale::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function findActiveForUserBox(int $userId, int $cajaId, int $bodegaId): Collection
    {
        return QueuedSale::query()
            ->where('user_id', $userId)
            ->where('caja_id', $cajaId)
            ->where('bodega_id', $bodegaId)
            ->whereNotIn('status', ['EDITING', 'COMPLETED', 'CANCELLED'])
            ->orderByDesc('created_at')
            ->get();
    }
}
