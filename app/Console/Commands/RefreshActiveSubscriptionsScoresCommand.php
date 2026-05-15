<?php

namespace App\Console\Commands;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Services\Plan\FamilyPlanServiceInterface;
use App\Services\Plan\PsychologyPlanServiceInterface;
use App\Services\Plan\WeightLossPlanServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class RefreshActiveSubscriptionsScoresCommand extends Command
{
    protected $signature = 'subscriptions:refresh-monthly-scores';

    protected $description = 'Create monthly subscription scores for active subscriptions according to each plan rule';

    public function handle(
        PsychologyPlanServiceInterface $psychologyPlanService,
        WeightLossPlanServiceInterface $weightLossPlanService,
        FamilyPlanServiceInterface $familyPlanService,
    ): int {
        Log::info('RefreshActiveSubscriptionsScoresCommand->handle');

        try {
            $created = 0;

            $psychologyPlan = $psychologyPlanService->getActivePsychologyPlan();
            $psychologySubscriptions = $psychologyPlan->subscriptions()->where('status', SubscriptionStatusEnum::active)->get();
            foreach ($psychologySubscriptions as $subscription) {
                if ($subscription->subscriptionScores()->where('created_at', '>=', Carbon::now()->startOfMonth())->exists()) {
                    continue;
                }
                $psychologyPlanService->createSubscriptionScores($subscription);
                $created += 4;
            }

            $weightLossPlan = $weightLossPlanService->getActiveWeightLossPlan();
            $weightLossSubscriptions = $weightLossPlan->subscriptions()->where('status', SubscriptionStatusEnum::active)->get();
            foreach ($weightLossSubscriptions as $subscription) {
                if ($subscription->subscriptionScores()->where('created_at', '>=', Carbon::now()->startOfMonth())->exists()) {
                    continue;
                }
                $beforeCount = $subscription->subscriptionScores()->count();
                $weightLossPlanService->createSubscriptionScores($subscription);
                $afterCount = $subscription->subscriptionScores()->count();
                $created += ($afterCount - $beforeCount);
            }

            $familyPlan = $familyPlanService->getActiveFamilyPlan();
            $familySubscriptions = $familyPlan->subscriptions()->where('status', SubscriptionStatusEnum::active)->get();
            foreach ($familySubscriptions as $subscription) {
                if ($subscription->subscriptionScores()->where('created_at', '>=', Carbon::now()->startOfMonth())->exists()) {
                    continue;
                }
                $beforeCount = $subscription->subscriptionScores()->count();
                $familyPlanService->createSubscriptionScores($subscription);
                $afterCount = $subscription->subscriptionScores()->count();
                $created += ($afterCount - $beforeCount);
            }

            $this->info('Created ' . $created . ' monthly subscription scores.');
            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('RefreshActiveSubscriptionsScoresCommand->handle-Error: ' . $e->getMessage());
            $this->error('Failed to refresh monthly subscription scores.');
            return self::FAILURE;
        }
    }
}
