<?php

namespace App\Observers;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepositoryInterface;
use App\Services\Plan\FamilyPlanServiceInterface;
use App\Services\Plan\PsychologyPlanServiceInterface;
use App\Services\Plan\WeightLossPlanServiceInterface;
use Illuminate\Support\Facades\Log;

class SubscriptionObserver
{
    public function updated(Subscription $subscription): void
    {
        Log::info('SubscriptionObserver->updated: ', ['subscriptionId' => $subscription->id, 'status' => $subscription->status]);

        try {
            if (!$subscription->wasChanged('status')) {
                return;
            }

            $weightLossPlanService = app(WeightLossPlanServiceInterface::class);
            $weightLossPlan = $weightLossPlanService->getActiveWeightLossPlan();

            if ($subscription->plan_id === $weightLossPlan->id) {
                $weightLossPlanService->syncNutritionExternalAccess($subscription);
            }

            if ($subscription->status !== SubscriptionStatusEnum::active) {
                return;
            }

            $subscriptionScoreRepository = app(SubscriptionScoreRepositoryInterface::class);
            $existingSubscriptionScores = $subscriptionScoreRepository->findBy([
                'subscription_id' => $subscription->id,
            ]);

            if ($existingSubscriptionScores->isNotEmpty()) {
                return;
            }

            $psychologyPlanService = app(PsychologyPlanServiceInterface::class);
            $psychologyPlan = $psychologyPlanService->getActivePsychologyPlan();

            if ($subscription->plan_id === $psychologyPlan->id) {
                $psychologyPlanService->createSubscriptionScores($subscription);
                return;
            }

            if ($subscription->plan_id === $weightLossPlan->id) {
                $weightLossPlanService->createSubscriptionScores($subscription);
                return;
            }

            $familyPlanService = app(FamilyPlanServiceInterface::class);
            $familyPlan = $familyPlanService->getActiveFamilyPlan();

            if ($subscription->plan_id === $familyPlan->id) {
                $familyPlanService->createSubscriptionScores($subscription);
            }
        } catch (\Exception $e) {
            Log::error('SubscriptionObserver->updated-Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
