<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_external_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('external_access_id');
            $table->string('external_key');
            $table->tinyInteger('status');
            $table->timestamps();

            $table->foreign('external_access_id')
                ->references('id')
                ->on('external_access_services')
                ->cascadeOnDelete();

            $table->unique(['external_access_id', 'external_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_external_accesses');
    }
};
