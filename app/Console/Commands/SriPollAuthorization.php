<?php

namespace App\Console\Commands;

use App\Models\Sri\ElectronicInvoice;
use App\Services\Sri\SriInvoiceService;
use Illuminate\Console\Command;

class SriPollAuthorization extends Command
{
    protected $signature = 'sri:poll-authorization {--limit=50} {--cooldown=10}';

    protected $description = 'Consulta autorizacion SRI por invoice con cooldown y limite por corrida.';

    public function handle(SriInvoiceService $sri): int
    {
        $limit = (int) $this->option('limit');
        $cooldown = (int) $this->option('cooldown');

        if ($limit <= 0) {
            $limit = 50;
        }
        if ($cooldown < 0) {
            $cooldown = 10;
        }

        $maxReviewHours = (int) config('sri.max_review_hours', 72);

        $query = ElectronicInvoice::query()
            ->whereIn('estado_sri', ['EN_PROCESO', 'ENVIADO'])
            ->where('updated_at', '<=', now()->subMinutes($cooldown))
            ->orderBy('updated_at')
            ->limit($limit);

        if ($sri->isProductionEnv()) {
            $query->where('updated_at', '>=', now()->subHours($maxReviewHours));
        }

        $invoices = $query->get(['id', 'clave_acceso']);

        if ($invoices->isEmpty()) {
            $this->info('SRI: no hay invoices pendientes para consultar.');
            return self::SUCCESS;
        }

        foreach ($invoices as $invoice) {
            $result = $sri->consultAuthorizationOnceByInvoiceId((int) $invoice->id);
            $status = strtoupper((string) ($result['status'] ?? ''));
            $this->line("SRI: invoice #{$invoice->id} -> {$status}");
        }

        return self::SUCCESS;
    }
}
