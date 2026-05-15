<?php

namespace App\Services\Plan;

use App\Enums\Plan\PlanStatusEnum;
use App\Enums\Professional\ConcilTypeEnum;
use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Enums\SubscriptionScore\SubscriptionScoreStatusEnum;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Repositories\Plan\PlanRepositoryInterface;
use App\Repositories\Score\ScoreRepositoryInterface;
use App\Repositories\Specialization\SpecializationRepositoryInterface;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Repositories\SubscriptionMember\SubscriptionMemberRepositoryInterface;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FamilyPlanService implements FamilyPlanServiceInterface
{

    public function __construct(
        protected PlanRepositoryInterface $planRepository,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected SpecializationRepositoryInterface $specializationRepository,
        protected ScoreRepositoryInterface $scoreRepository,
        protected SubscriptionScoreRepositoryInterface $subscriptionScoreRepository,
        protected SubscriptionMemberRepositoryInterface $subscriptionMemberRepository,
    ) {}

    public function subscribe(string $externalKey): Subscription
    {
        Log::info('FamilyPlanService->subscribe: ', ['externalKey' => $externalKey]);

        try {
            return DB::transaction(function () use ($externalKey) {
                $plan = $this->getActiveFamilyPlan();

                return $this->subscriptionRepository->create([
                    'plan_id' => $plan->id,
                    'external_key' => $externalKey,
                    'status' => SubscriptionStatusEnum::pending,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('FamilyPlanService->subscribe-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createSubscriptionScores(Subscription $subscription): void
    {
        Log::info('FamilyPlanService->createSubscriptionScores: ', ['subscriptionId' => $subscription->id]);

        try {
            $clinicalScore = $this->getClinicalMedicalScore();

            if (!$clinicalScore) {
                return;
            }

            $this->subscriptionScoreRepository->create([
                'subscription_id' => $subscription->id,
                'score_id' => $clinicalScore->id,
                'status' => SubscriptionScoreStatusEnum::enable,
            ]);
        } catch (\Exception $e) {
            Log::error('FamilyPlanService->createSubscriptionScores-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getActiveFamilyPlan()
    {
        Log::info('FamilyPlanService->getActiveFamilyPlan');

        try {
            $plan = $this->planRepository->findOneBy([
                'name' => 'Plano familiar',
                'status' => PlanStatusEnum::active,
            ]);

            if (!$plan) {
                throw new InvalidArgumentException('Family plan is not active');
            }

            return $plan;
        } catch (\Exception $e) {
            Log::error('FamilyPlanService->getActiveFamilyPlan-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function addMember(string $holderExternalKey, int $subscriptionId, string $externalKey): SubscriptionMember
    {
        Log::info('FamilyPlanService->addMember: ', ['holderExternalKey' => $holderExternalKey, 'subscriptionId' => $subscriptionId, 'externalKey' => $externalKey]);

        try {
            return DB::transaction(function () use ($holderExternalKey, $subscriptionId, $externalKey) {
                $subscription = $this->subscriptionRepository->findById($subscriptionId);

                if (!$subscription) {
                    throw new InvalidArgumentException('Subscription not found');
                }

                if ($subscription->external_key !== $holderExternalKey) {
                    throw new InvalidArgumentException('Only the holder can add members to this plan');
                }

                if ($subscription->status !== SubscriptionStatusEnum::active) {
                    throw new InvalidArgumentException('The family plan must be active to add members');
                }

                $plan = $this->getActiveFamilyPlan();
                if ($subscription->plan_id !== $plan->id) {
                    throw new InvalidArgumentException('Subscription is not from the family plan');
                }

                if ($externalKey === $subscription->external_key) {
                    throw new InvalidArgumentException('The holder is already part of this plan');
                }

                $existing = $this->subscriptionMemberRepository->findOneBy([
                    'subscription_id' => $subscription->id,
                    'external_key' => $externalKey,
                ]);
                if ($existing) {
                    throw new InvalidArgumentException('Member is already linked to this family plan');
                }

                $membersCount = 1 + $this->subscriptionMemberRepository->findBy([
                    'subscription_id' => $subscription->id,
                ])->count();
                if ($membersCount >= 4) {
                    throw new InvalidArgumentException('Family plan has reached the maximum number of users');
                }

                return $this->subscriptionMemberRepository->create([
                    'subscription_id' => $subscription->id,
                    'external_key' => $externalKey,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('FamilyPlanService->addMember-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getClinicalMedicalScore()
    {
        Log::info('FamilyPlanService->getClinicalMedicalScore');

        try {
            $specialization = $this->specializationRepository->findOneBy(['name' => 'Clinica Medica']);
            if (!$specialization) {
                return null;
            }

            return $this->scoreRepository->findOneBy([
                'specialization_id' => $specialization->id,
                'concil_type' => ConcilTypeEnum::doctor,
            ]);
        } catch (\Exception $e) {
            Log::error('FamilyPlanService->getClinicalMedicalScore-Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
