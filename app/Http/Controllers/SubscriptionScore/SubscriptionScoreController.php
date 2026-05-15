<?php

namespace App\Http\Controllers\SubscriptionScore;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionScore\FindAvailableSubscriptionScoreRequest;
use App\Http\Requests\SubscriptionScore\UseSubscriptionScoreRequest;
use App\Http\Resources\SubscriptionScoreResource;
use App\Services\SubscriptionScore\SubscriptionScoreServiceInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SubscriptionScoreController extends Controller
{
    public function __construct(protected SubscriptionScoreServiceInterface $service) {}

    public function findAvailable(FindAvailableSubscriptionScoreRequest $request)
    {
        try {
            $subscriptionScore = $this->service->findAvailable($request->validated());

            if (!$subscriptionScore) {
                return response()->json(['error' => 'Nao tem score para esse tipo de consulta'], 404);
            }

            return new SubscriptionScoreResource($subscriptionScore);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('SubscriptionScoreController->findAvailable-Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to find subscription score'], 500);
        }
    }

    public function use(UseSubscriptionScoreRequest $request)
    {
        try {
            return new SubscriptionScoreResource($this->service->use((int) $request->validated('score_id')));
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('SubscriptionScoreController->use-Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to use subscription score'], 500);
        }
    }
}
