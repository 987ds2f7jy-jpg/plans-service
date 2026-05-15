<?php

namespace App\Services\Payment;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExternalPaymentApiService implements ExternalPaymentApiServiceInterface
{
    public function __construct(protected HttpFactory $http) {}

    public function createPayment(array $payload): array
    {
        $response = $this->client()->post('/payments', $payload);

        if (!$response->successful()) {
            Log::error('ExternalPaymentApiService->createPayment-Error', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException('Unable to create payment with external API');
        }

        return $response->json();
    }

    public function getPayment(string $paymentId): array
    {
        $response = $this->client()->get('/payments/' . $paymentId);

        if (!$response->successful()) {
            Log::error('ExternalPaymentApiService->getPayment-Error', [
                'paymentId' => $paymentId,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException('Unable to fetch payment status from external API');
        }

        return $response->json();
    }

    public function validateWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.payment_api.webhook_secret');

        if (blank($secret)) {
            return true;
        }

        if (blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        if (hash_equals($expected, $signature)) {
            return true;
        }

        if (str_contains($signature, '=')) {
            [, $signedValue] = array_pad(explode('=', $signature, 2), 2, null);
            return filled($signedValue) && hash_equals($expected, $signedValue);
        }

        return false;
    }

    protected function client()
    {
        $baseUrl = rtrim((string) config('services.payment_api.base_url'), '/');
        $apiKey = config('services.payment_api.api_key');

        if (blank($baseUrl) || blank($apiKey)) {
            throw new RuntimeException('Payment API configuration is incomplete');
        }

        return $this->http
            ->baseUrl($baseUrl)
            ->timeout((int) config('services.payment_api.timeout', 10))
            ->acceptJson()
            ->asJson()
            ->withHeaders(array_filter([
                'X-API-Key' => $apiKey,
                'X-Request-Id' => app()->bound('request')
                    ? Arr::get(app('request')->headers->all(), 'x-request-id.0')
                    : null,
            ]));
    }
}
