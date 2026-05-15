<?php

namespace App\Services\SubscriptionScore;

use App\Enums\Subscription\SubscriptionStatusEnum;
use App\Enums\SubscriptionScore\SubscriptionScoreStatusEnum;
use App\Models\SubscriptionScore;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Repositories\SubscriptionMember\SubscriptionMemberRepositoryInterface;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SubscriptionScoreService implements SubscriptionScoreServiceInterface
{
    public function __construct(
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected SubscriptionScoreRepositoryInterface $subscriptionScoreRepository,
        protected SubscriptionMemberRepositoryInterface $subscriptionMemberRepository,
    ) {}

    public function findAvailable(array $data): ?SubscriptionScore
    {
        Log::info('SubscriptionScoreService->findAvailable: ', ['data' => $data]);

        try {
            $subscription = $this->subscriptionRepository->findOneBy([
                'plan_id' => $data['plan_id'],
                'external_key' => $data['external_key'],
                'status' => SubscriptionStatusEnum::active,
            ]);

            if (!$subscription) {
                $member = $this->subscriptionMemberRepository->findOneBy([
                    'external_key' => $data['external_key'],
                ]);

                if (!$member) {
                    return null;
                }

                $subscription = $this->subscriptionRepository->findOneBy([
                    'id' => $member->subscription_id,
                    'plan_id' => $data['plan_id'],
                    'status' => SubscriptionStatusEnum::active,
                ]);

                if (!$subscription) {
                    return null;
                }
            }

            $subscriptionScores = $this->subscriptionScoreRepository->findBy([
                'subscription_id' => $subscription->id,
                'status' => SubscriptionScoreStatusEnum::enable,
            ], relations: ['score', 'subscription']);

            return $subscriptionScores->first(
                fn (SubscriptionScore $subscriptionScore) => $subscriptionScore->score?->specialization_id === (int) $data['specialization_id']
            );
        } catch (\Exception $e) {
            Log::error('SubscriptionScoreService->findAvailable-Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function use(int $subscriptionScoreId): SubscriptionScore
    {
        Log::info('SubscriptionScoreService->use: ', ['subscriptionScoreId' => $subscriptionScoreId]);

        try {
            return DB::transaction(function () use ($subscriptionScoreId) {
                $subscriptionScore = $this->subscriptionScoreRepository->findAndLock($subscriptionScoreId);

                if (!$subscriptionScore) {
                    throw new InvalidArgumentException('Subscription score not found');
                }

                if ($subscriptionScore->status !== SubscriptionScoreStatusEnum::enable) {
                    throw new InvalidArgumentException('This score has already been used');
                }

                $updated = $this->subscriptionScoreRepository->update($subscriptionScoreId, [
                    'status' => SubscriptionScoreStatusEnum::used,
                ]);

                return $updated->load(['score', 'subscription']);
            });
        } catch (\Exception $e) {
            Log::error('SubscriptionScoreService->use-Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
