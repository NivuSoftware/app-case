<?php

namespace App\Jobs;

use App\Services\Sales\QueuedSaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmitQueuedSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(
        public int $queueId,
        public int $expectedVersion,
    ) {
    }

    public function handle(QueuedSaleService $service): void
    {
        $service->processEmission($this->queueId, $this->expectedVersion);
    }
}
