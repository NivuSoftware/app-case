<?php

namespace Database\Seeders;

use App\Models\Inventory\Inventory;
use App\Models\Product\Product;
use App\Models\Product\ProductPrice;
use App\Models\Store\Bodega;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ProductsMigrationExcelSeeder extends Seeder
{
    public function run(): void
    {
        $relativePath = 'productos/migracion.xlsx';
        $absolutePath = Storage::disk('sri')->path($relativePath);

        if (!file_exists($absolutePath)) {
            throw new \RuntimeException("No existe el archivo: storage/app/{$relativePath}");
        }

        if (!Bodega::query()->whereKey(1)->exists()) {
            throw new \RuntimeException('No existe la bodega con ID 1.');
        }

        $script = base_path('scripts/xlsx/products-to-json.cjs');
        $process = new Process(['node', $script, $absolutePath]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: 'No se pudo leer el archivo XLSX.');
        }

        $rows = json_decode($process->getOutput(), true);
        if (!is_array($rows)) {
            throw new \RuntimeException('El archivo XLSX no devolvio un arreglo de filas valido.');
        }

        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (!is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $processed++;

            $nombre = trim((string) ($row['nombre'] ?? ''));
            if ($nombre === '') {
                $skipped++;
                continue;
            }

            $codigoInterno = $this->nullable(trim((string) ($row['codigo_interno'] ?? '')));
            $codigoBarras = $this->nullable(trim((string) ($row['codigo_barras'] ?? '')));
            $categoria = $this->nullable(trim((string) ($row['categoria'] ?? '')));
            $unidadMedida = trim((string) ($row['unidad_medida'] ?? '')) ?: 'unidad';
            $descripcion = $this->nullable(trim((string) ($row['descripcion'] ?? '')));

            $stockMinimo = max(0, (int) round($this->toNumber($row['stock_minimo'] ?? 0)));
            $ivaPorcentaje = round($this->toNumber($row['iva_porcentaje'] ?? 15), 2);
            $precioUnitario = round(max(0, $this->toNumber($row['precio_unitario'] ?? 0)), 2);
            $stock = max(0, (int) round($this->toNumber($row['stock'] ?? 0)));

            DB::transaction(function () use (
                $nombre,
                $codigoInterno,
                $codigoBarras,
                $categoria,
                $unidadMedida,
                $descripcion,
                $stockMinimo,
                $ivaPorcentaje,
                $precioUnitario,
                $stock,
                &$created,
                &$updated
            ) {
                $product = $this->findProduct($codigoInterno, $codigoBarras, $nombre);

                $data = [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'codigo_barras' => $codigoBarras,
                    'codigo_interno' => $codigoInterno,
                    'categoria' => $categoria,
                    'unidad_medida' => $unidadMedida,
                    'stock_minimo' => $stockMinimo,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'estado' => true,
                ];

                if ($product) {
                    $product->fill($data)->save();
                    $updated++;
                } else {
                    $product = Product::query()->create($data);
                    $created++;
                }

                ProductPrice::query()->updateOrCreate(
                    ['producto_id' => $product->id],
                    ['precio_unitario' => $precioUnitario]
                );

                Inventory::query()->updateOrCreate(
                    [
                        'producto_id' => $product->id,
                        'bodega_id' => 1,
                        'percha_id' => null,
                    ],
                    [
                        'stock_actual' => $stock,
                        'stock_reservado' => 0,
                    ]
                );
            });
        }

        $this->command?->info("Filas procesadas: {$processed}");
        $this->command?->info("Productos creados: {$created}");
        $this->command?->info("Productos actualizados: {$updated}");
        $this->command?->info("Filas omitidas: {$skipped}");
    }

    private function findProduct(?string $codigoInterno, ?string $codigoBarras, string $nombre): ?Product
    {
        if ($codigoInterno !== null) {
            $product = Product::query()->where('codigo_interno', $codigoInterno)->first();
            if ($product) {
                return $product;
            }
        }

        if ($codigoBarras !== null) {
            $product = Product::query()->where('codigo_barras', $codigoBarras)->first();
            if ($product) {
                return $product;
            }
        }

        return Product::query()->where('nombre', $nombre)->first();
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function toNumber(mixed $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $commaPos = strrpos($value, ',');
        $dotPos = strrpos($value, '.');

        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($commaPos !== false) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
