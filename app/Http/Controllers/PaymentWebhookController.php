<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentWebhookRequest;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Services\Payment\ExternalPaymentApiServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected ExternalPaymentApiServiceInterface $externalPaymentApiService,
    ) {}

    public function __invoke(PaymentWebhookRequest $request)
    {
        $signatureHeader = config('services.payment_api.webhook_signature_header', 'X-Webhook-Signature');
        $signature = $request->header($signatureHeader);

        if (!$this->externalPaymentApiService->validateWebhookSignature($request->getContent(), $signature)) {
            Log::warning('PaymentWebhookController->invalidSignature', [
                'header' => $signatureHeader,
            ]);

            return response()->json(['error' => 'invalid signature'], 401);
        }

        ProcessPaymentWebhookJob::dispatch(
            $request->validated(),
            $this->normalizeHeaders($request)
        );

        return response()->json(['status' => 'accepted'], 202);
    }

    protected function normalizeHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->map(fn (array $values) => count($values) === 1 ? $values[0] : $values)
            ->all();
    }
}
