<?php

namespace App\Providers;

use App\Models\Subscription;
use App\Observers\SubscriptionObserver;
use App\Repositories\ExternalAccessService\ExternalAccessServiceRepository;
use App\Repositories\ExternalAccessService\ExternalAccessServiceRepositoryInterface;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Payment\PaymentRepositoryInterface;
use App\Repositories\PaymentWebhookEvent\PaymentWebhookEventRepository;
use App\Repositories\PaymentWebhookEvent\PaymentWebhookEventRepositoryInterface;
use App\Repositories\Plan\PlanRepository;
use App\Repositories\Plan\PlanRepositoryInterface;
use App\Repositories\Score\ScoreRepository;
use App\Repositories\Score\ScoreRepositoryInterface;
use App\Repositories\Specialization\SpecializationRepository;
use App\Repositories\Specialization\SpecializationRepositoryInterface;
use App\Repositories\Subscription\SubscriptionRepository;
use App\Repositories\Subscription\SubscriptionRepositoryInterface;
use App\Repositories\SubscriptionMember\SubscriptionMemberRepository;
use App\Repositories\SubscriptionMember\SubscriptionMemberRepositoryInterface;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepository;
use App\Repositories\SubscriptionScore\SubscriptionScoreRepositoryInterface;
use App\Repositories\UserExternalAccess\UserExternalAccessRepository;
use App\Repositories\UserExternalAccess\UserExternalAccessRepositoryInterface;
use App\Services\Payment\ExternalPaymentApiService;
use App\Services\Payment\ExternalPaymentApiServiceInterface;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\Plan\FamilyPlanService;
use App\Services\Plan\FamilyPlanServiceInterface;
use App\Services\Plan\PsychologyPlanService;
use App\Services\Plan\PsychologyPlanServiceInterface;
use App\Services\Plan\WeightLossPlanService;
use App\Services\Plan\WeightLossPlanServiceInterface;
use App\Services\SubscriptionScore\SubscriptionScoreService;
use App\Services\SubscriptionScore\SubscriptionScoreServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PsychologyPlanServiceInterface::class, PsychologyPlanService::class);
        $this->app->bind(WeightLossPlanServiceInterface::class, WeightLossPlanService::class);
        $this->app->bind(FamilyPlanServiceInterface::class, FamilyPlanService::class);
        $this->app->bind(SubscriptionScoreServiceInterface::class, SubscriptionScoreService::class);
        $this->app->bind(ExternalPaymentApiServiceInterface::class, ExternalPaymentApiService::class);
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);

        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(ScoreRepositoryInterface::class, ScoreRepository::class);
        $this->app->bind(SpecializationRepositoryInterface::class, SpecializationRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(SubscriptionScoreRepositoryInterface::class, SubscriptionScoreRepository::class);
        $this->app->bind(SubscriptionMemberRepositoryInterface::class, SubscriptionMemberRepository::class);
        $this->app->bind(ExternalAccessServiceRepositoryInterface::class, ExternalAccessServiceRepository::class);
        $this->app->bind(UserExternalAccessRepositoryInterface::class, UserExternalAccessRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(PaymentWebhookEventRepositoryInterface::class, PaymentWebhookEventRepository::class);
    }

    public function boot(): void
    {
        Subscription::observe(SubscriptionObserver::class);
    }
}
