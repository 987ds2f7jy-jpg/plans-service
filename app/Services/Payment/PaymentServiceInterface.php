<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Subscription;

interface PaymentServiceInterface
{
    public function initializeSubscriptionPayment(Subscription $subscription, array $payload = []): ?Payment;

    public function syncPaymentStatus(Payment $payment): Payment;

    public function handleWebhook(array $payload, array $headers = []): void;
}
