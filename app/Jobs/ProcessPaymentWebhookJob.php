<?php

namespace App\Jobs;

use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public array $payload,
        public array $headers = [],
    ) {}

    public function handle(PaymentServiceInterface $paymentService): void
    {
        Log::info('ProcessPaymentWebhookJob->handle', [
            'externalPaymentId' => $this->payload['id'] ?? null,
            'status' => $this->payload['status'] ?? null,
        ]);

        $paymentService->handleWebhook($this->payload, $this->headers);
    }
}
