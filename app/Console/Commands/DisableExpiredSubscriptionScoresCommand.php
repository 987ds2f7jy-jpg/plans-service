<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionScore\SubscriptionScoreStatusEnum;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DisableExpiredSubscriptionScoresCommand extends Command
{
    protected $signature = 'subscriptions:disable-expired-scores';

    protected $description = 'Disable subscription scores that have been enabled for one month or more';

    public function handle(SubscriptionScoreRepositoryInterface $subscriptionScoreRepository): int
    {
        Log::info('DisableExpiredSubscriptionScoresCommand->handle');

        try {
            $expiredScores = $subscriptionScoreRepository->findBy([
                ['created_at', '<=', Carbon::now()->subMonth()],
                'status' => SubscriptionScoreStatusEnum::enable,
            ]);

            foreach ($expiredScores as $expiredScore) {
                $subscriptionScoreRepository->update($expiredScore->id, [
                    'status' => SubscriptionScoreStatusEnum::disable,
                ]);
            }

            $this->info('Disabled ' . $expiredScores->count() . ' expired subscription scores.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('DisableExpiredSubscriptionScoresCommand->handle-Error: ' . $e->getMessage());
            $this->error('Failed to disable expired subscription scores.');

            return self::FAILURE;
        }
    }
}
