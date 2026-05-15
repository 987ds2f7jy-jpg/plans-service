<?php

namespace App\Services\Plan;

use App\Enums\Plan\PlanStatusEnum;
use App\Enums\Professional\ConcilTypeEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Enums\SubscriptionScore\SubscriptionScoreStatusEnum;
use App\Models\Subscription;
use App\Repositories\Plan\PlanRepositoryInterface;
use App\Repositories\Score\ScoreRepositoryInterface;
use App\Repositories\Specialization\SpecializationRepositoryInterface;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PsychologyPlanService implements PsychologyPlanServiceInterface
{
    public function __construct(
        protected PlanRepositoryInterface $planRepository,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected SpecializationRepositoryInterface $specializationRepository,
        protected ScoreRepositoryInterface $scoreRepository,
        protected SubscriptionScoreRepositoryInterface $subscriptionScoreRepository,
    ) {}

    public function subscribe(string $externalKey): Subscription
    {
        Log::info('PsychologyPlanService->subscribe: ', ['externalKey' => $externalKey]);

        try {
            return DB::transaction(function () use ($externalKey) {
                $plan = $this->getActivePsychologyPlan();

                return $this->subscriptionRepository->create([
                    'plan_id' => $plan->id,
                    'external_key' => $externalKey,
                    'status' => SubscriptionStatusEnum::pending,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('PsychologyPlanService->subscribe-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createSubscriptionScores(Subscription $subscription): void
    {
        Log::info('PsychologyPlanService->createSubscriptionScores: ', ['subscriptionId' => $subscription->id]);

        try {
            $psychologyScore = $this->getPsychologyScore();

            if (!$psychologyScore) {
                return;
            }

            $payload = [];
            for ($index = 0; $index < 4; $index++) {
                $payload[] = [
                    'subscription_id' => $subscription->id,
                    'score_id' => $psychologyScore->id,
                    'status' => SubscriptionScoreStatusEnum::enable,
                ];
            }

            $this->subscriptionScoreRepository->createMany($payload);
        } catch (\Exception $e) {
            Log::error('PsychologyPlanService->createSubscriptionScores-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getActivePsychologyPlan()
    {
        Log::info('PsychologyPlanService->getActivePsychologyPlan');

        try {
            $plan = $this->planRepository->findOneBy([
                'name' => 'Plano de psicologia',
                'status' => PlanStatusEnum::active,
            ]);

            if (!$plan) {
                throw new InvalidArgumentException('Psychology plan is not active');
            }

            return $plan;
        } catch (\Exception $e) {
            Log::error('PsychologyPlanService->getActivePsychologyPlan-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getPsychologyScore()
    {
        Log::info('PsychologyPlanService->getPsychologyScore');

        try {
            $specialization = $this->specializationRepository->findOneBy([
                'council_type' => ConcilTypeEnum::psychologist,
            ], orderBy: 'id');

            if (!$specialization) {
                return null;
            }

            return $this->scoreRepository->findOneBy([
                'specialization_id' => $specialization->id,
                'concil_type' => ConcilTypeEnum::psychologist,
            ]);
        } catch (\Exception $e) {
            Log::error('PsychologyPlanService->getPsychologyScore-Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
