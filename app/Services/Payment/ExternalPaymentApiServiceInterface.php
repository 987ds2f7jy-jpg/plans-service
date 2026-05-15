<?php

namespace App\Services\Payment;

interface ExternalPaymentApiServiceInterface
{
    public function createPayment(array $payload): array;

    public function getPayment(string $paymentId): array;

    public function validateWebhookSignature(string $payload, ?string $signature): bool;
}
