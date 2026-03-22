<?php

namespace Database\Seeders;

use App\Models\Clients\Client;
use App\Models\Clients\ClientEmail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ClientsMigrationExcelSeeder extends Seeder
{
    public function run(): void
    {
        $relativePath = 'clients/clients.xlsx';
        $absolutePath = Storage::disk('sri')->path($relativePath);

        if (!file_exists($absolutePath)) {
            throw new \RuntimeException("No existe el archivo: storage/app/{$relativePath}");
        }

        $nodeBinary = (new ExecutableFinder())->find('node');
        if ($nodeBinary === null) {
            throw new \RuntimeException(
                'No se encontro el ejecutable "node". Instala Node.js o ajusta el PATH antes de correr este seeder.'
            );
        }

        $script = base_path('scripts/xlsx/clients-to-json.cjs');
        $process = new Process([$nodeBinary, $script, $absolutePath]);
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
        $emailsCreated = 0;
        $skipped = 0;
        $failed = 0;
        $failures = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row) || $this->isEmptyRow($row)) {
                continue;
            }

            $processed++;
            $excelRow = $index + 2;

            $identificacion = trim((string) ($row['identificacion'] ?? ''));
            $business = trim((string) ($row['business'] ?? ''));
            $tipoIdentificacion = strtoupper(trim((string) ($row['tipo'] ?? '')));
            $telefono = $this->nullable(trim((string) ($row['telefono'] ?? '')));
            $ciudad = $this->nullable(trim((string) ($row['ciudad'] ?? '')));
            $email = strtolower(trim((string) ($row['email'] ?? '')));

            if (
                $identificacion === ''
                || $business === ''
                || !in_array($tipoIdentificacion, ['CEDULA', 'RUC', 'PASAPORTE'], true)
            ) {
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use (
                    $identificacion,
                    $business,
                    $tipoIdentificacion,
                    $telefono,
                    $ciudad,
                    $email,
                    &$created,
                    &$updated,
                    &$emailsCreated
                ) {
                    $client = Client::query()->updateOrCreate(
                        [
                            'tipo_identificacion' => $tipoIdentificacion,
                            'identificacion' => $identificacion,
                        ],
                        [
                            'business' => $business,
                            'tipo' => $this->resolveTipoCliente($tipoIdentificacion),
                            'telefono' => $telefono,
                            'direccion' => null,
                            'ciudad' => $ciudad,
                            'estado' => 'activo',
                        ]
                    );

                    if ($client->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        return;
                    }

                    $emailExists = ClientEmail::query()
                        ->where('client_id', $client->id)
                        ->where('email', $email)
                        ->exists();

                    if ($emailExists) {
                        return;
                    }

                    ClientEmail::query()->create([
                        'client_id' => $client->id,
                        'email' => $email,
                    ]);

                    $emailsCreated++;
                });
            } catch (\Throwable $e) {
                $failed++;
                $failures[] = [
                    'row' => $excelRow,
                    'identificacion' => $identificacion,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->command?->newLine();
        $this->command?->info('Resumen importacion de clientes');
        $this->command?->info("Filas procesadas: {$processed}");
        $this->command?->info("Clientes creados: {$created}");
        $this->command?->info("Clientes actualizados: {$updated}");
        $this->command?->info("Emails creados: {$emailsCreated}");
        $this->command?->info("Filas omitidas: {$skipped}");
        $this->command?->info("Filas fallidas: {$failed}");

        if ($failed > 0) {
            $this->command?->warn('Detalle de fallos:');

            foreach (array_slice($failures, 0, 20) as $failure) {
                $identificacionLabel = $failure['identificacion'] !== ''
                    ? " | Identificacion: {$failure['identificacion']}"
                    : '';

                $this->command?->warn(
                    "Fila Excel {$failure['row']}{$identificacionLabel} | Error: {$failure['error']}"
                );
            }

            if (count($failures) > 20) {
                $remaining = count($failures) - 20;
                $this->command?->warn("... y {$remaining} fallo(s) adicional(es).");
            }
        }
    }

    private function resolveTipoCliente(string $tipoIdentificacion): string
    {
        return $tipoIdentificacion === 'RUC' ? 'juridico' : 'natural';
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
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
