<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_plan_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('external_key');
            $table->string('plan_code');
            $table->string('external_payment_reference')->unique();
            $table->timestamp('paid_at');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->json('customer');
            $table->json('metadata')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['external_key', 'plan_code']);
            $table->index(['subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_plan_activations');
    }
};
