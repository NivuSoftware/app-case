<?php

namespace App\Services\Sales;

use App\Jobs\EmitQueuedSaleJob;
use App\Models\Clients\Client;
use App\Models\Clients\ClientEmail;
use App\Models\Sales\QueuedSale;
use App\Models\User;
use App\Repositories\Sales\QueuedSaleRepository;
use App\Services\Sri\SriInvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueuedSaleService
{
    public const DEFAULT_DURATION_SECONDS = 60;

    public function __construct(
        private QueuedSaleRepository $queues,
        private SaleService $sales,
        private SriInvoiceService $sriInvoiceService,
    ) {
    }

    public function enqueue(array $data): QueuedSale
    {
        $prepared = $this->sales->prepareSaleDraft($data);
        $duration = self::DEFAULT_DURATION_SECONDS;

        $queue = DB::transaction(function () use ($data, $prepared, $duration) {
            $reservation = $this->sriInvoiceService->reserveInvoiceNumber();
            $accessKey = $this->sriInvoiceService->buildAccessKeyForSequence(
                (int) $reservation['sequence'],
                $prepared['sale_data']['fecha_venta']
            );
            $prepared['reserved_access_key'] = $accessKey['clave_acceso'];
            $prepared['reserved_codigo_numerico'] = $accessKey['codigo_numerico'];

            return $this->queues->create([
                'user_id' => (int) $data['user_id'],
                'caja_id' => (int) $prepared['caja_id'],
                'bodega_id' => (int) $prepared['sale_data']['bodega_id'],
                'client_id' => $prepared['sale_data']['client_id'],
                'client_email_id' => $prepared['sale_data']['client_email_id'],
                'email_destino' => $prepared['sale_data']['email_destino'],
                'fecha_venta' => $prepared['sale_data']['fecha_venta'],
                'tipo_documento' => $prepared['sale_data']['tipo_documento'] ?? 'FACTURA',
                'observaciones' => $prepared['sale_data']['observaciones'],
                'iva_enabled' => (bool) ($prepared['iva_enabled'] ?? true),
                'payload_json' => ['prepared' => $prepared],
                'status' => 'QUEUED',
                'duration_seconds' => $duration,
                'remaining_seconds' => $duration,
                'execute_at' => now()->addSeconds($duration),
                'schedule_version' => 1,
                'reserved_num_factura' => $reservation['num_factura'],
                'reserved_sequence' => $reservation['sequence'],
            ]);
        });

        $this->dispatchEmissionJob($queue);

        return $queue->fresh();
    }

    public function requeue(QueuedSale $queue, array $data): QueuedSale
    {
        if ($queue->status !== 'EDITING') {
            throw ValidationException::withMessages([
                'queue' => 'La factura seleccionada no está lista para reencolar.',
            ]);
        }

        $prepared = $this->sales->prepareSaleDraft($data);
        $duration = self::DEFAULT_DURATION_SECONDS;

        DB::transaction(function () use ($queue, $prepared, $duration) {
            $queue->refresh();
            if ($queue->status !== 'EDITING') {
                throw ValidationException::withMessages([
                    'queue' => 'La factura dejó de estar disponible para reencolar.',
                ]);
            }

            $accessKey = $this->sriInvoiceService->buildAccessKeyForSequence(
                (int) $queue->reserved_sequence,
                $prepared['sale_data']['fecha_venta']
            );
            $prepared['reserved_access_key'] = $accessKey['clave_acceso'];
            $prepared['reserved_codigo_numerico'] = $accessKey['codigo_numerico'];

            $queue->fill([
                'caja_id' => (int) $prepared['caja_id'],
                'bodega_id' => (int) $prepared['sale_data']['bodega_id'],
                'client_id' => $prepared['sale_data']['client_id'],
                'client_email_id' => $prepared['sale_data']['client_email_id'],
                'email_destino' => $prepared['sale_data']['email_destino'],
                'fecha_venta' => $prepared['sale_data']['fecha_venta'],
                'tipo_documento' => $prepared['sale_data']['tipo_documento'] ?? 'FACTURA',
                'observaciones' => $prepared['sale_data']['observaciones'],
                'iva_enabled' => (bool) ($prepared['iva_enabled'] ?? true),
                'payload_json' => ['prepared' => $prepared],
                'status' => 'QUEUED',
                'duration_seconds' => $duration,
                'remaining_seconds' => $duration,
                'execute_at' => now()->addSeconds($duration),
                'schedule_version' => (int) $queue->schedule_version + 1,
                'last_error' => null,
            ]);
            $queue->save();
        });

        $queue = $queue->fresh();
        $this->dispatchEmissionJob($queue);

        return $queue;
    }

    public function listForContext(int $userId, int $cajaId, int $bodegaId): array
    {
        $queues = $this->queues->findActiveForUserBox($userId, $cajaId, $bodegaId);

        return [
            'items' => $queues->map(fn(QueuedSale $queue) => $this->serializeQueue($queue))->values()->all(),
            'total_count' => $queues->count(),
            'server_now' => now()->toIso8601String(),
        ];
    }

    public function pause(QueuedSale $queue): QueuedSale
    {
        if ($queue->status !== 'QUEUED') {
            throw ValidationException::withMessages([
                'queue' => 'Solo puedes pausar facturas que están en cola.',
            ]);
        }

        DB::transaction(function () use ($queue) {
            $queue->refresh();
            if ($queue->status !== 'QUEUED') {
                throw ValidationException::withMessages([
                    'queue' => 'La factura ya no está disponible para pausar.',
                ]);
            }

            $remaining = $this->remainingSecondsUntil($queue->execute_at);

            $queue->status = 'PAUSED';
            $queue->remaining_seconds = $remaining;
            $queue->execute_at = null;
            $queue->schedule_version = (int) $queue->schedule_version + 1;
            $queue->save();
        });

        return $queue->fresh();
    }

    public function resume(QueuedSale $queue): QueuedSale
    {
        if ($queue->status !== 'PAUSED') {
            throw ValidationException::withMessages([
                'queue' => 'Solo puedes reanudar facturas pausadas.',
            ]);
        }

        DB::transaction(function () use ($queue) {
            $queue->refresh();
            if ($queue->status !== 'PAUSED') {
                throw ValidationException::withMessages([
                    'queue' => 'La factura ya no está disponible para reanudar.',
                ]);
            }

            $remaining = max(1, (int) ($queue->remaining_seconds ?? self::DEFAULT_DURATION_SECONDS));
            $queue->status = 'QUEUED';
            $queue->remaining_seconds = $remaining;
            $queue->execute_at = now()->addSeconds($remaining);
            $queue->schedule_version = (int) $queue->schedule_version + 1;
            $queue->save();
        });

        $queue = $queue->fresh();
        $this->dispatchEmissionJob($queue);

        return $queue;
    }

    public function edit(QueuedSale $queue): array
    {
        if (!in_array($queue->status, ['QUEUED', 'PAUSED', 'FAILED'], true)) {
            throw ValidationException::withMessages([
                'queue' => 'La factura no se puede editar en su estado actual.',
            ]);
        }

        DB::transaction(function () use ($queue) {
            $queue->refresh();
            if (!in_array($queue->status, ['QUEUED', 'PAUSED', 'FAILED'], true)) {
                throw ValidationException::withMessages([
                    'queue' => 'La factura dejó de estar disponible para editar.',
                ]);
            }

            $queue->status = 'EDITING';
            $queue->execute_at = null;
            $queue->schedule_version = (int) $queue->schedule_version + 1;
            $queue->save();
        });

        $queue = $queue->fresh();
        $prepared = $this->getPreparedPayload($queue);

        return [
            'queue_id' => $queue->id,
            'reserved_num_factura' => $queue->reserved_num_factura,
            'payload' => $prepared['restore_payload'] ?? [
                'cart' => [],
                'client' => null,
            ],
        ];
    }

    public function cancel(QueuedSale $queue): void
    {
        if (in_array($queue->status, ['COMPLETED', 'CANCELLED', 'PROCESSING'], true)) {
            throw ValidationException::withMessages([
                'queue' => 'La factura ya no se puede cancelar.',
            ]);
        }

        DB::transaction(function () use ($queue) {
            $queue->refresh();
            if (in_array($queue->status, ['COMPLETED', 'CANCELLED', 'PROCESSING'], true)) {
                throw ValidationException::withMessages([
                    'queue' => 'La factura ya no se puede cancelar.',
                ]);
            }

            $queue->status = 'CANCELLED';
            $queue->execute_at = null;
            $queue->remaining_seconds = max(0, (int) $queue->remaining_seconds);
            $queue->schedule_version = (int) $queue->schedule_version + 1;
            $queue->save();

            $this->sriInvoiceService->releaseReservedInvoiceNumber(
                (int) ($queue->reserved_sequence ?? 0),
                (string) ($queue->reserved_num_factura ?? ''),
                (int) $queue->id
            );
        });
    }

    public function processEmission(int $queueId, int $expectedVersion): ?QueuedSale
    {
        $queue = DB::transaction(function () use ($queueId, $expectedVersion) {
            /** @var QueuedSale|null $queue */
            $queue = QueuedSale::query()->lockForUpdate()->find($queueId);
            if (!$queue) {
                return null;
            }

            if (
                $queue->status !== 'QUEUED' ||
                (int) $queue->schedule_version !== $expectedVersion ||
                !($queue->execute_at instanceof Carbon) ||
                $queue->execute_at->gt(now())
            ) {
                return null;
            }

            $queue->status = 'PROCESSING';
            $queue->save();

            return $queue->fresh();
        });

        if (!$queue) {
            return null;
        }

        try {
            $prepared = $this->ensureReservedAccessKey($queue);

            $sale = $this->sales->crearVentaDesdeDraft($prepared, [
                'reserved_num_factura' => $queue->reserved_num_factura,
                'reserved_sequence' => $queue->reserved_sequence,
                'reserved_codigo_numerico' => $prepared['reserved_codigo_numerico'] ?? null,
                'dispatch_sri_job' => true,
            ]);

            DB::transaction(function () use ($queue, $sale, $expectedVersion) {
                $locked = QueuedSale::query()->lockForUpdate()->findOrFail($queue->id);
                if ((int) $locked->schedule_version !== $expectedVersion || $locked->status !== 'PROCESSING') {
                    return;
                }

                $locked->status = 'COMPLETED';
                $locked->sale_id = $sale->id;
                $locked->execute_at = null;
                $locked->remaining_seconds = 0;
                $locked->last_error = null;
                $locked->save();
            });

            return $queue->fresh();
        } catch (\Throwable $e) {
            DB::transaction(function () use ($queue, $expectedVersion, $e) {
                $locked = QueuedSale::query()->lockForUpdate()->find($queue->id);
                if (!$locked || (int) $locked->schedule_version !== $expectedVersion) {
                    return;
                }

                $locked->status = 'FAILED';
                $locked->execute_at = null;
                $locked->last_error = mb_substr($e->getMessage(), 0, 2000);
                $locked->save();
            });

            report($e);

            return $queue->fresh();
        }
    }

    public function buildTicketSaleViewModel(QueuedSale $queue): object
    {
        $prepared = $this->ensureReservedAccessKey($queue);
        $saleData = $prepared['sale_data'] ?? [];
        $clientSnapshot = $prepared['client_snapshot'] ?? [];
        $restorePayload = $prepared['restore_payload'] ?? [];

        $client = $this->buildTicketClient(
            $queue->client_id ? Client::find($queue->client_id) : null,
            $clientSnapshot
        );

        $clientEmail = $queue->client_email_id ? ClientEmail::find($queue->client_email_id) : null;
        $user = User::find($queue->user_id);

        $items = collect(array_map(function (array $item): object {
            return (object) [
                'cantidad' => $item['cantidad'],
                'descripcion' => $item['descripcion'],
                'precio_unitario' => $item['precio_unitario'],
                'descuento' => $item['descuento'] ?? 0,
                'iva_porcentaje' => $item['iva_porcentaje'] ?? 0,
                'total' => $item['total'],
                'producto' => null,
            ];
        }, $prepared['items'] ?? []));

        $payments = collect(array_map(function (array $payment): object {
            return (object) [
                'monto' => $payment['monto'] ?? 0,
                'metodo' => $payment['metodo'] ?? null,
                'paymentMethod' => null,
                'monto_recibido' => $payment['monto_recibido'] ?? null,
                'cambio' => $payment['cambio'] ?? null,
            ];
        }, $prepared['payments'] ?? []));

        $viewSale = new \stdClass();
        $viewSale->id = 0;
        $viewSale->num_factura = $queue->reserved_num_factura;
        $viewSale->fecha_venta = $saleData['fecha_venta'] ?? $queue->fecha_venta;
        $viewSale->client = $client;
        $viewSale->clientEmail = $clientEmail;
        $viewSale->email_destino = $saleData['email_destino'] ?? $queue->email_destino;
        $viewSale->user = $user ?: (object) ['name' => 'Usuario'];
        $viewSale->items = $items;
        $viewSale->payments = $payments;
        $viewSale->subtotal = $saleData['subtotal'] ?? 0;
        $viewSale->descuento = $saleData['descuento'] ?? 0;
        $viewSale->iva = $saleData['iva'] ?? 0;
        $viewSale->total = $saleData['total'] ?? 0;
        $viewSale->cliente_nombre = $clientSnapshot['name'] ?? null;
        $viewSale->cliente_identificacion = $clientSnapshot['ident'] ?? null;
        $viewSale->cliente_email = $clientSnapshot['clientEmail'] ?? null;
        $viewSale->clave_acceso = $prepared['reserved_access_key'] ?? null;
        $viewSale->restore_payload = $restorePayload;

        return $viewSale;
    }

    public function serializeQueue(QueuedSale $queue): array
    {
        $prepared = $this->getPreparedPayload($queue);
        $saleData = $prepared['sale_data'] ?? [];
        $client = $prepared['client_snapshot'] ?? [];
        $meta = $prepared['meta'] ?? ['lineas' => 0, 'unidades' => 0, 'preview' => 'Sin detalle'];

        return [
            'id' => $queue->id,
            'status' => $queue->status,
            'created_at' => optional($queue->created_at)->toIso8601String(),
            'execute_at' => optional($queue->execute_at)->toIso8601String(),
            'remaining_seconds' => (int) ($queue->remaining_seconds ?? 0),
            'duration_seconds' => (int) ($queue->duration_seconds ?? self::DEFAULT_DURATION_SECONDS),
            'reserved_num_factura' => $queue->reserved_num_factura,
            'total' => (float) ($saleData['total'] ?? 0),
            'client_name' => $client['name'] ?? 'Consumidor final',
            'client_ident' => $client['ident'] ?? '9999999999999',
            'meta' => [
                'lineas' => (int) ($meta['lineas'] ?? 0),
                'unidades' => (int) ($meta['unidades'] ?? 0),
                'preview' => (string) ($meta['preview'] ?? 'Sin detalle'),
            ],
            'sale_id' => $queue->sale_id,
            'last_error' => $queue->last_error,
        ];
    }

    private function getPreparedPayload(QueuedSale $queue): array
    {
        $payload = $queue->payload_json ?? [];

        return is_array($payload['prepared'] ?? null)
            ? $payload['prepared']
            : (is_array($payload) ? $payload : []);
    }

    private function ensureReservedAccessKey(QueuedSale $queue): array
    {
        $prepared = $this->getPreparedPayload($queue);

        if (!empty($prepared['reserved_access_key']) && !empty($prepared['reserved_codigo_numerico'])) {
            return $prepared;
        }

        if ((int) ($queue->reserved_sequence ?? 0) <= 0) {
            return $prepared;
        }

        $fechaVenta = $prepared['sale_data']['fecha_venta'] ?? $queue->fecha_venta ?? now();
        $accessKey = $this->sriInvoiceService->buildAccessKeyForSequence(
            (int) $queue->reserved_sequence,
            $fechaVenta
        );

        $prepared['reserved_access_key'] = $accessKey['clave_acceso'];
        $prepared['reserved_codigo_numerico'] = $accessKey['codigo_numerico'];

        $payload = $queue->payload_json ?? [];
        if (!is_array($payload)) {
            $payload = [];
        }

        $payload['prepared'] = $prepared;
        $queue->payload_json = $payload;
        $queue->save();

        return $prepared;
    }

    private function dispatchEmissionJob(QueuedSale $queue): void
    {
        if (!$queue->execute_at) {
            return;
        }

        EmitQueuedSaleJob::dispatch($queue->id, (int) $queue->schedule_version)
            ->delay($queue->execute_at);
    }

    private function buildTicketClient(?Client $client, array $snapshot): object
    {
        if (!$client) {
            return (object) [
                'nombre' => $snapshot['name'] ?? 'Consumidor final',
                'business' => $snapshot['name'] ?? 'Consumidor final',
                'identificacion' => $snapshot['ident'] ?? '9999999999999',
                'telefono' => '-',
                'direccion' => '-',
            ];
        }

        return (object) [
            'nombre' => $client->business,
            'business' => $client->business,
            'identificacion' => $client->identificacion,
            'telefono' => $client->telefono,
            'direccion' => $client->direccion,
        ];
    }

    private function remainingSecondsUntil(?Carbon $executeAt): int
    {
        if (!$executeAt) {
            return 1;
        }

        $remainingMs = $executeAt->valueOf() - now()->valueOf();

        if ($remainingMs <= 0) {
            return 1;
        }

        return max(1, (int) ceil($remainingMs / 1000));
    }
}
