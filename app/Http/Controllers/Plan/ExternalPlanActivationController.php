<?php

namespace App\Http\Controllers\Plan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\ActivateExternalPlanRequest;
use App\Services\Plan\ExternalPlanActivationServiceInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ExternalPlanActivationController extends Controller
{
    public function __construct(protected ExternalPlanActivationServiceInterface $service) {}

    public function __invoke(ActivateExternalPlanRequest $request)
    {
        try {
            $activation = $this->service->activate($request->validated());
            $status = $activation->wasRecentlyCreated ? 201 : 200;

            return response()->json([
                'data' => [
                    'id' => $activation->id,
                    'external_payment_reference' => $activation->external_payment_reference,
                    'plan_code' => $activation->plan_code,
                    'external_key' => $activation->external_key,
                    'activated_at' => $activation->activated_at,
                    'subscription' => [
                        'id' => $activation->subscription->id,
                        'plan_id' => $activation->subscription->plan_id,
                        'external_key' => $activation->subscription->external_key,
                        'status' => $activation->subscription->status?->value,
                        'payment_verified_at' => $activation->subscription->payment_verified_at,
                    ],
                ],
            ], $status);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('ExternalPlanActivationController->activate-Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to activate external plan'], 500);
        }
    }
}
