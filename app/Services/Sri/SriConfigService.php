<?php

namespace App\Services\Sri;

use App\Models\Sri\SriConfig;
use App\Repositories\Sri\SriConfigRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SriConfigService
{
    public function __construct(private SriConfigRepository $repo) {}

    public function get(): ?SriConfig
    {
        return $this->repo->first();
    }

    public function save(array $data, ?UploadedFile $certFile): SriConfig
    {
        if ($certFile) {
            // Guardamos en storage/app/sri/certs/
            $name = 'cert_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $certFile->getClientOriginalExtension();

            $path = $certFile->storeAs('sri/certs', $name); 
            $data['ruta_certificado'] = $path;

            $current = $this->repo->first();
            if ($current?->ruta_certificado && Storage::exists($current->ruta_certificado)) {
                if ($current->ruta_certificado !== $path) {
                    Storage::delete($current->ruta_certificado);
                }
            }
        } else {
            unset($data['ruta_certificado']);
        }

        return $this->repo->upsert($data);
    }

    public function getOrFailForUpdate(): \App\Models\Sri\SriConfig
    {
        $cfg = \App\Models\Sri\SriConfig::query()->lockForUpdate()->first();

        if (!$cfg) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sri' => 'No existe configuración SRI. Debes registrarla primero en el panel.',
            ]);
        }

        return $cfg;
    }

}
