<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->string('external_id')->unique();
            $table->string('provider')->nullable();
            $table->string('provider_payment_id')->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('metadata')->nullable();
            $table->json('customer')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
