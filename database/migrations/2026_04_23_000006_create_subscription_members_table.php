<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('external_key');
            $table->timestamps();
            $table->unique(['subscription_id', 'external_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_members');
    }
};
