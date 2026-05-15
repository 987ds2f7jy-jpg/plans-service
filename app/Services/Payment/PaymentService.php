<?php

namespace App\Services\Payment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Payment;
use App\Models\Subscription;
use App\Repositories\Payment\PaymentRepositoryInterface;
use App\Repositories\PaymentWebhookEvent\PaymentWebhookEventRepositoryInterface;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        protected ExternalPaymentApiServiceInterface $externalPaymentApiService,
        protected PaymentRepositoryInterface $paymentRepository,
        protected PaymentWebhookEventRepositoryInterface $paymentWebhookEventRepository,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    public function initializeSubscriptionPayment(Subscription $subscription, array $payload = []): ?Payment
    {
        Log::info('PaymentService->initializeSubscriptionPayment', [
            'subscriptionId' => $subscription->id,
            'planId' => $subscription->plan_id,
        ]);

        $subscription->loadMissing('plan', 'payment');
        $plan = $subscription->plan;

        if ((float) $plan->price <= 0) {
            Log::info('PaymentService->initializeSubscriptionPayment-FreePlan', [
                'subscriptionId' => $subscription->id,
            ]);

            $this->applySubscriptionStatus($subscription, SubscriptionStatusEnum::active);

            return null;
        }

        if ($subscription->payment) {
            return $this->syncPaymentStatus($subscription->payment);
        }

        $paymentMethod = Arr::get($payload, 'payment_method');
        if (blank($paymentMethod)) {
            throw new InvalidArgumentException('payment_method is required for paid plans');
        }

        $requestPayload = [
            'external_id' => $this->buildExternalId($subscription),
            'amount' => (float) $plan->price,
            'payment_method' => $paymentMethod,
            'webhook_url' => $this->resolveWebhookUrl(),
            'metadata' => array_filter([
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $plan->name,
            ] + (Arr::get($payload, 'metadata', []))),
            'customer' => [
                'email' => Arr::get($payload, 'customer.email', $subscription->external_key),
                'first_name' => Arr::get($payload, 'customer.first_name'),
                'last_name' => Arr::get($payload, 'customer.last_name'),
                'document' => Arr::get($payload, 'customer.document'),
            ],
        ];

        if ($paymentMethod === 'credit_card') {
            $requestPayload['card'] = array_filter([
                'token' => Arr::get($payload, 'card.token'),
                'issuer_id' => Arr::get($payload, 'card.issuer_id'),
                'installments' => Arr::get($payload, 'card.installments'),
                'payment_method_id' => Arr::get($payload, 'card.payment_method_id'),
            ], fn ($value) => !is_null($value));
        }

        $response = $this->externalPaymentApiService->createPayment($requestPayload);

        $payment = DB::transaction(function () use ($subscription, $requestPayload, $response) {
            $payment = $this->paymentRepository->create([
                'subscription_id' => $subscription->id,
                'gateway_payment_id' => Arr::get($response, 'id'),
                'external_id' => Arr::get($response, 'external_id', $requestPayload['external_id']),
                'provider' => Arr::get($response, 'provider', config('services.payment_api.provider')),
                'provider_payment_id' => Arr::get($response, 'provider_payment_id'),
                'amount' => Arr::get($response, 'amount', $requestPayload['amount']),
                'payment_method' => Arr::get($response, 'payment_method', $requestPayload['payment_method']),
                'status' => Arr::get($response, 'status', PaymentStatusEnum::pending->value),
                'paid_at' => Arr::get($response, 'paid_at'),
                'webhook_url' => Arr::get($response, 'webhook_url', $requestPayload['webhook_url']),
                'metadata' => Arr::get($response, 'metadata', $requestPayload['metadata']),
                'customer' => Arr::get($response, 'customer', $requestPayload['customer']),
                'provider_response' => Arr::get($response, 'provider_response'),
                'last_synced_at' => now(),
            ]);

            $this->applyPaymentStatus($subscription, $payment);

            return $payment;
        });

        Log::info('PaymentService->initializeSubscriptionPayment-Created', [
            'subscriptionId' => $subscription->id,
            'paymentId' => $payment->id,
            'status' => $payment->status?->value,
        ]);

        return $payment->refresh();
    }

    public function syncPaymentStatus(Payment $payment): Payment
    {
        Log::info('PaymentService->syncPaymentStatus', [
            'paymentId' => $payment->id,
            'externalPaymentId' => $payment->provider_payment_id,
        ]);

        $paymentId = $payment->gateway_payment_id ?: $payment->external_id;
        $response = $this->externalPaymentApiService->getPayment($paymentId);

        return DB::transaction(function () use ($payment, $response) {
            $payment = $this->updatePaymentFromPayload($payment, $response);
            $this->applyPaymentStatus($payment->subscription()->firstOrFail(), $payment);

            return $payment->refresh();
        });
    }

    public function handleWebhook(array $payload, array $headers = []): void
    {
        $eventKey = $this->buildWebhookEventKey($payload);

        Log::info('PaymentService->handleWebhook', [
            'eventKey' => $eventKey,
            'paymentExternalId' => Arr::get($payload, 'id'),
            'status' => Arr::get($payload, 'status'),
        ]);

        $existingEvent = $this->paymentWebhookEventRepository->findOneBy(['event_key' => $eventKey]);
        if ($existingEvent && $existingEvent->status === 'processed') {
            Log::warning('PaymentService->handleWebhook-Duplicate', ['eventKey' => $eventKey]);
            return;
        }

        $event = $existingEvent ?: $this->paymentWebhookEventRepository->create([
            'event_key' => $eventKey,
            'provider' => Arr::get($payload, 'provider'),
            'external_payment_id' => Arr::get($payload, 'id'),
            'status' => 'processing',
            'payload' => $payload,
            'headers' => $headers,
        ]);

        try {
            DB::transaction(function () use ($payload, $event) {
                $payment = $this->paymentRepository->findOneBy(['external_id' => Arr::get($payload, 'external_id')]);

                if (!$payment && filled(Arr::get($payload, 'id'))) {
                    $payment = $this->paymentRepository->findOneBy(['gateway_payment_id' => Arr::get($payload, 'id')]);
                }

                if (!$payment) {
                    throw new InvalidArgumentException('Payment not found for webhook payload');
                }

                $payment = $this->updatePaymentFromPayload($payment, $payload);
                $this->applyPaymentStatus($payment->subscription()->firstOrFail(), $payment);

                $this->paymentWebhookEventRepository->update($event->id, [
                    'status' => 'processed',
                    'processed_at' => now(),
                    'error_message' => null,
                ]);
            });
        } catch (\Throwable $e) {
            $this->paymentWebhookEventRepository->update($event->id, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('PaymentService->handleWebhook-Error', [
                'eventKey' => $eventKey,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function updatePaymentFromPayload(Payment $payment, array $payload): Payment
    {
        $updated = $this->paymentRepository->update($payment->id, [
            'gateway_payment_id' => Arr::get($payload, 'id', $payment->gateway_payment_id),
            'provider' => Arr::get($payload, 'provider', $payment->provider),
            'provider_payment_id' => Arr::get($payload, 'provider_payment_id', Arr::get($payload, 'id', $payment->provider_payment_id)),
            'status' => Arr::get($payload, 'status', $payment->status?->value ?? PaymentStatusEnum::pending->value),
            'paid_at' => Arr::get($payload, 'paid_at'),
            'webhook_url' => Arr::get($payload, 'webhook_url', $payment->webhook_url),
            'metadata' => Arr::get($payload, 'metadata', $payment->metadata),
            'customer' => Arr::get($payload, 'customer', $payment->customer),
            'provider_response' => Arr::get($payload, 'provider_response', $payment->provider_response),
            'last_synced_at' => now(),
        ]);

        Log::info('PaymentService->updatePaymentFromPayload', [
            'paymentId' => $updated->id,
            'status' => $updated->status?->value,
        ]);

        return $updated;
    }

    protected function applyPaymentStatus(Subscription $subscription, Payment $payment): void
    {
        $status = $payment->status instanceof PaymentStatusEnum
            ? $payment->status
            : PaymentStatusEnum::from((string) $payment->status);

        $subscriptionStatus = match ($status) {
            PaymentStatusEnum::approved => SubscriptionStatusEnum::active,
            PaymentStatusEnum::pending => SubscriptionStatusEnum::pending,
            PaymentStatusEnum::cancelled => SubscriptionStatusEnum::cancelled,
            PaymentStatusEnum::rejected => SubscriptionStatusEnum::inactive,
        };

        $this->applySubscriptionStatus($subscription, $subscriptionStatus, $status === PaymentStatusEnum::approved);
    }

    protected function applySubscriptionStatus(
        Subscription $subscription,
        SubscriptionStatusEnum $status,
        bool $verified = false,
    ): void {
        $updates = ['status' => $status];

        $updates['payment_verified_at'] = $verified ? now() : null;

        $this->subscriptionRepository->update($subscription->id, $updates);
    }

    protected function buildExternalId(Subscription $subscription): string
    {
        return 'subscription_' . $subscription->id;
    }

    protected function resolveWebhookUrl(): string
    {
        $configured = config('services.payment_api.webhook_url');

        if (filled($configured)) {
            return $configured;
        }

        return rtrim(config('app.url'), '/') . '/api/payments/webhook';
    }

    protected function buildWebhookEventKey(array $payload): string
    {
        return sha1(json_encode([
            'id' => Arr::get($payload, 'id'),
            'external_id' => Arr::get($payload, 'external_id'),
            'status' => Arr::get($payload, 'status'),
            'paid_at' => Arr::get($payload, 'paid_at'),
        ]));
    }
}
