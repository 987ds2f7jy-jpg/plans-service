<?php

namespace App\Services\Plan;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Models\ExternalPlanActivation;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExternalPlanActivationService implements ExternalPlanActivationServiceInterface
{
    public function __construct(
        protected PsychologyPlanServiceInterface $psychologyPlanService,
        protected WeightLossPlanServiceInterface $weightLossPlanService,
        protected FamilyPlanServiceInterface $familyPlanService,
    ) {}

    public function activate(array $data): ExternalPlanActivation
    {
        $plan = $this->resolveActivePlan($data['plan_code']);
        $activation = $this->findOrCreateActivation($data, $plan);

        return DB::transaction(function () use ($activation, $data, $plan) {
            $activation = ExternalPlanActivation::query()
                ->whereKey($activation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($activation->subscription_id) {
                return $activation->load(['subscription.plan', 'subscription.subscriptionScores.score']);
            }

            $subscription = $this->createAndActivateSubscription($data['external_key'], $plan, $data['paid_at']);

            $activation->forceFill([
                'subscription_id' => $subscription->id,
                'activated_at' => now(),
            ])->save();

            $activation = $activation->refresh()->load(['subscription.plan', 'subscription.subscriptionScores.score']);
            $activation->wasRecentlyCreated = true;

            return $activation;
        });
    }

    protected function findOrCreateActivation(array $data, Plan $plan): ExternalPlanActivation
    {
        $activation = ExternalPlanActivation::query()
            ->where('external_payment_reference', $data['external_payment_reference'])
            ->first();

        if ($activation) {
            return $activation;
        }

        try {
            return ExternalPlanActivation::query()->create([
                'plan_id' => $plan->id,
                'external_key' => $data['external_key'],
                'plan_code' => $data['plan_code'],
                'external_payment_reference' => $data['external_payment_reference'],
                'paid_at' => $data['paid_at'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'customer' => $data['customer'],
                'metadata' => $data['metadata'] ?? null,
            ]);
        } catch (QueryException $e) {
            $activation = ExternalPlanActivation::query()
                ->where('external_payment_reference', $data['external_payment_reference'])
                ->first();

            if ($activation) {
                return $activation;
            }

            throw $e;
        }
    }

    protected function createAndActivateSubscription(string $externalKey, Plan $plan, string $paidAt): Subscription
    {
        $subscription = Subscription::query()->create([
            'plan_id' => $plan->id,
            'external_key' => $externalKey,
            'status' => SubscriptionStatusEnum::pending,
        ]);

        $subscription->forceFill([
            'status' => SubscriptionStatusEnum::active,
            'payment_verified_at' => $paidAt,
        ])->save();

        return $subscription->refresh();
    }

    protected function resolveActivePlan(string $planCode): Plan
    {
        return match ($planCode) {
            'psychology' => $this->psychologyPlanService->getActivePsychologyPlan(),
            'weight_loss' => $this->weightLossPlanService->getActiveWeightLossPlan(),
            'family' => $this->familyPlanService->getActiveFamilyPlan(),
            default => throw new InvalidArgumentException('Unsupported plan_code'),
        };
    }
}
