<?php

use App\Http\Controllers\Plan\PlanController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\SubscriptionScore\SubscriptionScoreController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/webhook', PaymentWebhookController::class);

Route::prefix('plans')->group(function () {
    Route::post('/psychology/subscribe', [PlanController::class, 'subscribePsychologyPlan']);
    Route::post('/weight-loss/subscribe', [PlanController::class, 'subscribeWeightLossPlan']);
    Route::post('/family/subscribe', [PlanController::class, 'subscribeFamilyPlan']);
    Route::post('/family/add-member', [PlanController::class, 'addFamilyPlanMember']);
});

Route::prefix('subscription-score')->group(function () {
    Route::post('/find', [SubscriptionScoreController::class, 'findAvailable']);
    Route::post('/use', [SubscriptionScoreController::class, 'use']);
});
