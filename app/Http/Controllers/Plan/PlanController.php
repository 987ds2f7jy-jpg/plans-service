<?php

namespace App\Http\Controllers\Plan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\AddFamilyPlanMemberRequest;
use App\Http\Requests\Plan\SubscribePlanRequest;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\Plan\FamilyPlanServiceInterface;
use App\Services\Plan\PsychologyPlanServiceInterface;
use App\Services\Plan\WeightLossPlanServiceInterface;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PlanController extends Controller
{
    public function __construct(
        protected PsychologyPlanServiceInterface $psychologyPlanService,
        protected WeightLossPlanServiceInterface $weightLossPlanService,
        protected FamilyPlanServiceInterface $familyPlanService,
        protected PaymentServiceInterface $paymentService,
    ) {}

    public function subscribePsychologyPlan(SubscribePlanRequest $request)
    {
        try {
            $subscription = $this->psychologyPlanService->subscribe($request->validated('external_key'));
            $this->paymentService->initializeSubscriptionPayment($subscription, $request->validated());

            return response()->json($subscription->fresh()->load('payment'), 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('PlanController->subscribePsychologyPlan-Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to subscribe external user to psychology plan'], 500);
        }
    }

    public function subscribeWeightLossPlan(SubscribePlanRequest $request)
    {
        try {
            $subscription = $this->weightLossPlanService->subscribe($request->validated('external_key'));
            $this->paymentService->initializeSubscriptionPayment($subscription, $request->validated());

            return response()->json($subscription->fresh()->load('payment'), 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('PlanController->subscribeWeightLossPlan-Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to subscribe external user to weight loss plan'], 500);
        }
    }

    public function subscribeFamilyPlan(SubscribePlanRequest $request)
    {
        try {
            $subscription = $this->familyPlanService->subscribe($request->validated('external_key'));
            $this->paymentService->initializeSubscriptionPayment($subscription, $request->validated());

            return response()->json($subscription->fresh()->load('payment'), 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('PlanController->subscribeFamilyPlan-Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to subscribe external user to family plan'], 500);
        }
    }

    public function addFamilyPlanMember(AddFamilyPlanMemberRequest $request)
    {
        try {
            $member = $this->familyPlanService->addMember(
                $request->validated('holder_external_key'),
                (int) $request->validated('subscription_id'),
                $request->validated('external_key')
            );

            return response()->json($member, 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('PlanController->addFamilyPlanMember-Error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to add member to family plan'], 500);
        }
    }
}
