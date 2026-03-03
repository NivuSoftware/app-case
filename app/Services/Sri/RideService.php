<?php

namespace App\Services\Sri;

use App\Models\Sales\Sale;
use App\Models\Sri\SriConfig;
use App\Repositories\Sri\ElectronicInvoiceRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RideService
{
    public function __construct(
        private SriConfigService $configService,
        private ElectronicInvoiceRepository $repo
    ) {}

    public function generateForSale(int $saleId): string
    {
        $disk = (string) config('sri.documents_disk', 'local');

        $sale = Sale::with([
            'items',
            'client',
            'payments.paymentMethod',
        ])->findOrFail($saleId);

        $invoice = $this->repo->findBySaleId($sale->id);

        if (!$invoice) {
            throw ValidationException::withMessages([
                'sri' => 'No existe electronic_invoice para esta venta.',
            ]);
        }

        if (strtoupper((string)($invoice->estado_sri ?? '')) !== 'AUTORIZADO') {
            throw ValidationException::withMessages([
                'sri' => 'La factura no está AUTORIZADA, no se puede generar RIDE.',
            ]);
        }

        $cfg = $this->configService->get();
        if (!$cfg instanceof SriConfig) {
            throw ValidationException::withMessages([
                'sri' => 'No existe configuración SRI.',
            ]);
        }

        $clave = (string)($invoice->clave_acceso ?? '');
        if ($clave === '') {
            throw ValidationException::withMessages([
                'sri' => 'Falta clave_acceso en electronic_invoices.',
            ]);
        }

        $pdf = Pdf::loadView('sri.ride.factura', [
            'sale' => $sale,
            'invoice' => $invoice,
            'cfg' => $cfg,
        ])->setPaper('A4', 'portrait');

        $path = "sri/ride/{$clave}.pdf";
        $this->putRidePdf($disk, $path, $pdf->output(), $saleId, (int) $invoice->id);

        // recomendado: guardar ruta
        $invoice->ride_pdf_path = $path;
        $invoice->save();

        return $path;
    }

    private function putRidePdf(string $disk, string $path, string $contents, int $saleId, int $invoiceId): void
    {
        $diskConfig = (array) config("filesystems.disks.{$disk}", []);
        $ctx = [
            'stage' => 'store_ride_pdf',
            'sale_id' => $saleId,
            'invoice_id' => $invoiceId,
            'disk' => $disk,
            'driver' => $diskConfig['driver'] ?? null,
            'bucket' => $diskConfig['bucket'] ?? null,
            'region' => $diskConfig['region'] ?? null,
            'visibility' => $diskConfig['visibility'] ?? null,
            'directory_visibility' => $diskConfig['directory_visibility'] ?? null,
            'path' => $path,
            'bytes' => strlen($contents),
        ];

        Log::info('SRI RIDE storage write start', $ctx);

        try {
            $ok = Storage::disk($disk)->put($path, $contents);
            if ($ok !== true) {
                throw new RuntimeException('Storage::put returned false');
            }
            Log::info('SRI RIDE storage write success', $ctx);
        } catch (Throwable $e) {
            $ctx['error'] = $e->getMessage();
            Log::error('SRI RIDE storage write failed', $ctx);
            throw $e;
        }
    }
}
