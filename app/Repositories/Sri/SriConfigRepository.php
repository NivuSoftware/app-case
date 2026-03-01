<?php

namespace App\Repositories\Sri;

use App\Models\Sri\SriConfig;

class SriConfigRepository
{
    public function first(): ?SriConfig
    {
        return SriConfig::query()->first();
    }

    /**
     * Upsert simple: si existe config -> update, si no -> create.
     */
    public function upsert(array $data): SriConfig
    {
        $cfg = SriConfig::query()->first();

        if ($cfg) {
            $cfg->fill($data);
            $cfg->save();
            return $cfg->fresh();
        }

        return SriConfig::create($data);
    }

    /**
     * Opcional: por si luego quieres manejar lock desde repo.
     */
    public function firstForUpdate(): ?SriConfig
    {
        return SriConfig::query()->lockForUpdate()->first();
    }
}
