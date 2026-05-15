<?php

namespace App\Services\Plan;

use App\Enums\Plan\PlanStatusEnum;
use App\Enums\Professional\ConcilTypeEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Enums\SubscriptionScore\SubscriptionScoreStatusEnum;
use App\Enums\UserExternalAccess\UserExternalAccessStatusEnum;
use App\Models\Subscription;
use App\Repositories\ExternalAccessService\ExternalAccessServiceRepositoryInterface;
use App\Repositories\Plan\PlanRepositoryInterface;
use App\Repositories\Score\ScoreRepositoryInterface;
use App\Repositories\Specialization\SpecializationRepositoryInterface;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepositoryInterface;
use App\Repositories\UserExternalAccess\UserExternalAccessRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WeightLossPlanService implements WeightLossPlanServiceInterface
{
    public function __construct(
        protected PlanRepositoryInterface $planRepository,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected SpecializationRepositoryInterface $specializationRepository,
        protected ScoreRepositoryInterface $scoreRepository,
        protected SubscriptionScoreRepositoryInterface $subscriptionScoreRepository,
        protected ExternalAccessServiceRepositoryInterface $externalAccessServiceRepository,
        protected UserExternalAccessRepositoryInterface $userExternalAccessRepository,
    ) {}

    public function subscribe(string $externalKey): Subscription
    {
        Log::info('WeightLossPlanService->subscribe: ', ['externalKey' => $externalKey]);

        try {
            return DB::transaction(function () use ($externalKey) {
                $plan = $this->getActiveWeightLossPlan();

                return $this->subscriptionRepository->create([
                    'plan_id' => $plan->id,
                    'external_key' => $externalKey,
                    'status' => SubscriptionStatusEnum::pending,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('WeightLossPlanService->subscribe-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createSubscriptionScores(Subscription $subscription): void
    {
        Log::info('WeightLossPlanService->createSubscriptionScores: ', ['subscriptionId' => $subscription->id]);

        try {
            $payload = [];

            foreach ($this->getWeightLossScores() as $score) {
                $payload[] = [
                    'subscription_id' => $subscription->id,
                    'score_id' => $score->id,
                    'status' => SubscriptionScoreStatusEnum::enable,
                ];
            }

            if (empty($payload)) {
                return;
            }

            $this->subscriptionScoreRepository->createMany($payload);
        } catch (\Exception $e) {
            Log::error('WeightLossPlanService->createSubscriptionScores-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function syncNutritionExternalAccess(Subscription $subscription): void
    {
        Log::info('WeightLossPlanService->syncNutritionExternalAccess: ', [
            'subscriptionId' => $subscription->id,
            'externalKey' => $subscription->external_key,
            'status' => $subscription->status,
        ]);

        try {
            $externalAccessService = $this->externalAccessServiceRepository->findById('app_nutricao');

            if (!$externalAccessService) {
                throw new InvalidArgumentException('External access service app_nutricao not found');
            }

            $userExternalAccess = $this->userExternalAccessRepository->findOneBy([
                'external_access_id' => $externalAccessService->id,
                'external_key' => $subscription->external_key,
            ]);

            $status = $subscription->status === SubscriptionStatusEnum::active
                ? UserExternalAccessStatusEnum::active
                : UserExternalAccessStatusEnum::blocked;

            if ($userExternalAccess) {
                $this->userExternalAccessRepository->update($userExternalAccess->id, ['status' => $status]);
                return;
            }

            $this->userExternalAccessRepository->create([
                'external_access_id' => $externalAccessService->id,
                'external_key' => $subscription->external_key,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('WeightLossPlanService->syncNutritionExternalAccess-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getActiveWeightLossPlan()
    {
        Log::info('WeightLossPlanService->getActiveWeightLossPlan');

        try {
            $plan = $this->planRepository->findOneBy([
                'name' => 'Plano de emagrecimento',
                'status' => PlanStatusEnum::active,
            ]);

            if (!$plan) {
                throw new InvalidArgumentException('Weight loss plan is not active');
            }

            return $plan;
        } catch (\Exception $e) {
            Log::error('WeightLossPlanService->getActiveWeightLossPlan-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getWeightLossScores(): array
    {
        Log::info('WeightLossPlanService->getWeightLossScores');

        try {
            $scores = [];

            $clinicalSpecialization = $this->specializationRepository->findOneBy(['name' => 'Clinica Medica']);
            if ($clinicalSpecialization) {
                $score = $this->scoreRepository->findOneBy([
                    'specialization_id' => $clinicalSpecialization->id,
                    'concil_type' => ConcilTypeEnum::doctor,
                ]);
                if ($score) {
                    $scores[] = $score;
                }
            }

            $nutritionSpecialization = $this->specializationRepository->findOneBy(['name' => 'Nutricao']);
            if ($nutritionSpecialization) {
                $score = $this->scoreRepository->findOneBy([
                    'specialization_id' => $nutritionSpecialization->id,
                    'concil_type' => ConcilTypeEnum::nutritionist,
                ]);
                if ($score) {
                    $scores[] = $score;
                }
            }

            $physicalEducatorSpecialization = $this->specializationRepository->findOneBy([
                'council_type' => ConcilTypeEnum::physicalEducator,
            ], orderBy: 'id');
            if ($physicalEducatorSpecialization) {
                $score = $this->scoreRepository->findOneBy([
                    'specialization_id' => $physicalEducatorSpecialization->id,
                    'concil_type' => ConcilTypeEnum::physicalEducator,
                ]);
                if ($score) {
                    $scores[] = $score;
                }
            }

            return $scores;
        } catch (\Exception $e) {
            Log::error('WeightLossPlanService->getWeightLossScores-Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
