<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\QueuedSale;
use App\Services\Sales\QueuedSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueuedSaleController extends Controller
{
    public function __construct(private QueuedSaleService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'caja_id' => 'required|integer|min:1',
            'bodega_id' => 'required|exists:bodegas,id',
        ]);

        return response()->json($this->service->listForContext(
            (int) $request->user()->id,
            (int) $data['caja_id'],
            (int) $data['bodega_id']
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateSalePayload($request);
        $data['user_id'] = (int) $request->user()->id;

        if ($request->user()?->bodega_id) {
            $data['bodega_id'] = $request->user()->bodega_id;
        }

        $queue = $this->service->enqueue($data);

        return response()->json([
            'message' => 'Factura en cola correctamente.',
            'data' => [
                'queue_id' => $queue->id,
                'reserved_num_factura' => $queue->reserved_num_factura,
                'execute_at' => optional($queue->execute_at)->toIso8601String(),
                'server_now' => now()->toIso8601String(),
                'ticket_url' => route('sales.queue.ticket', ['id' => $queue->id, 'autoprint' => 1, 'embed' => 1]),
                'queue' => $this->service->serializeQueue($queue),
            ],
        ], 201);
    }

    public function requeue(int $id, Request $request): JsonResponse
    {
        $queue = $this->findOwnedQueue($id, $request);
        $data = $this->validateSalePayload($request);
        $data['user_id'] = (int) $request->user()->id;

        if ($request->user()?->bodega_id) {
            $data['bodega_id'] = $request->user()->bodega_id;
        }

        $queue = $this->service->requeue($queue, $data);

        return response()->json([
            'message' => 'Factura actualizada y vuelta a cola.',
            'data' => [
                'queue_id' => $queue->id,
                'reserved_num_factura' => $queue->reserved_num_factura,
                'execute_at' => optional($queue->execute_at)->toIso8601String(),
                'server_now' => now()->toIso8601String(),
                'ticket_url' => route('sales.queue.ticket', ['id' => $queue->id, 'autoprint' => 1, 'embed' => 1]),
                'queue' => $this->service->serializeQueue($queue),
            ],
        ]);
    }

    public function pause(int $id, Request $request): JsonResponse
    {
        $queue = $this->service->pause($this->findOwnedQueue($id, $request));

        return response()->json([
            'message' => 'Factura en cola pausada.',
            'data' => [
                'queue' => $this->service->serializeQueue($queue),
                'server_now' => now()->toIso8601String(),
            ],
        ]);
    }

    public function resume(int $id, Request $request): JsonResponse
    {
        $queue = $this->service->resume($this->findOwnedQueue($id, $request));

        return response()->json([
            'message' => 'Factura en cola reanudada.',
            'data' => [
                'queue' => $this->service->serializeQueue($queue),
                'server_now' => now()->toIso8601String(),
            ],
        ]);
    }

    public function edit(int $id, Request $request): JsonResponse
    {
        $result = $this->service->edit($this->findOwnedQueue($id, $request));

        return response()->json([
            'message' => 'Factura en cola lista para editar.',
            'data' => $result,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $this->service->cancel($this->findOwnedQueue($id, $request));

        return response()->json([
            'message' => 'Factura en cola cancelada.',
            'server_now' => now()->toIso8601String(),
        ]);
    }

    private function validateSalePayload(Request $request): array
    {
        return $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'user_id' => 'nullable|exists:users,id',
            'bodega_id' => 'required|exists:bodegas,id',
            'caja_id' => 'required|integer|min:1',
            'client_email_id' => 'nullable|exists:client_emails,id',
            'email_destino' => 'nullable|string|max:255',
            'fecha_venta' => 'required|date',
            'tipo_documento' => 'nullable|string|max:20',
            'num_factura' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
            'iva_enabled' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:products,id',
            'items.*.descripcion' => 'required|string|max:255',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
            'items.*.descuento' => 'nullable|numeric|min:0',
            'items.*.iva_porcentaje' => 'nullable|numeric|min:0|max:100',
            'items.*.percha_id' => 'nullable|exists:perchas,id',
            'payments' => 'nullable|array|min:1',
            'payments.*.metodo' => 'nullable|string|max:20',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.monto' => 'required|numeric|min:0.01',
            'payments.*.monto_recibido' => 'nullable|numeric|min:0',
            'payments.*.referencia' => 'nullable|string|max:100',
            'payments.*.observaciones' => 'nullable|string|max:500',
            'payments.*.fecha_pago' => 'nullable|date',
            'payment' => 'nullable|array',
            'payment.metodo' => 'nullable|string|max:20',
            'payment.payment_method_id' => 'nullable|exists:payment_methods,id',
            'payment.monto' => 'nullable|numeric|min:0.01',
            'payment.monto_recibido' => 'nullable|numeric|min:0',
            'payment.referencia' => 'nullable|string|max:100',
            'payment.observaciones' => 'nullable|string|max:500',
            'payment.fecha_pago' => 'nullable|date',
            'cart_snapshot' => 'nullable|array',
            'client_snapshot' => 'nullable|array',
        ]);
    }

    private function findOwnedQueue(int $id, Request $request): QueuedSale
    {
        /** @var QueuedSale|null $queue */
        $queue = QueuedSale::query()
            ->where('id', $id)
            ->where('user_id', (int) $request->user()->id)
            ->first();

        abort_unless($queue, 404);

        return $queue;
    }
}
