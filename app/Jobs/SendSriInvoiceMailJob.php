<?php

namespace App\Jobs;

use App\Mail\SriInvoiceAuthorizedMail;
use App\Models\Sales\Sale;
use App\Repositories\Sri\ElectronicInvoiceRepository;
use App\Services\Sri\RideService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class SendSriInvoiceMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $saleId,
        public ?array $recipients = null
    ) {}

    public function handle(
        RideService $rideService,
        ElectronicInvoiceRepository $repo
    ): void {
        $disk = (string) config('sri.documents_disk', 'local');

        $sale = Sale::with(['client.emails', 'clientEmail'])->findOrFail($this->saleId);
        $invoice = $repo->findBySaleId($sale->id);

        if (!$invoice || strtoupper((string)($invoice->estado_sri ?? '')) !== 'AUTORIZADO') {
            return;
        }

        $xmlPath = (string)($invoice->xml_autorizado_path ?? '');
        if ($xmlPath === '' || !Storage::disk($disk)->exists($xmlPath)) {
            return;
        }

        $ridePath = (string)($invoice->ride_pdf_path ?? '');
        if ($ridePath === '' || !Storage::disk($disk)->exists($ridePath)) {
            $ridePath = (string) $rideService->generateForSale($sale->id);
        }

        if ($ridePath === '' || !Storage::disk($disk)->exists($ridePath)) {
            return;
        }

        $recipients = collect($this->recipients ?? $this->resolveRecipients($sale))
            ->map(fn ($email) => $this->normalizeEmail((string) $email))
            ->filter()
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            foreach ($recipients as $to) {
                Mail::to($to)->send(new SriInvoiceAuthorizedMail(
                    $sale,
                    $invoice,
                    $ridePath,
                    $xmlPath,
                    $disk
                ));
            }
        } catch (\Throwable $e) {
            Log::error('SendSriInvoiceMailJob FAIL', [
                'sale_id' => $sale->id,
                'to' => $recipients->all(),
                'ridePath' => $ridePath,
                'xmlPath' => $xmlPath,
                'error' => $e->getMessage(),
            ]);
            throw $e; // para que quede en failed_jobs con el error real
        }
    }

    private function resolveRecipients(Sale $sale): array
    {
        $saleEmail = $this->normalizeEmail((string) ($sale->email_destino ?? ''));
        if ($saleEmail) {
            return [$saleEmail];
        }

        $selectedClientEmail = $this->normalizeEmail((string) ($sale->clientEmail->email ?? ''));
        if ($selectedClientEmail) {
            return [$selectedClientEmail];
        }

        if ($sale->relationLoaded('client') && $sale->client) {
            foreach (($sale->client->emails ?? collect()) as $clientEmail) {
                $email = $this->normalizeEmail((string) ($clientEmail->email ?? ''));
                if ($email) {
                    return [$email];
                }
            }
        }

        return [];
    }

    private function normalizeEmail(string $email): ?string
    {
        $email = mb_strtolower(trim($email));

        return $email !== '' ? $email : null;
    }
}
