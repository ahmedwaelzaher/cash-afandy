<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create program definitions, participation attempts and their earned rewards.
     */
    public function up(): void
    {
        // Keep program content and eligibility in one definition.
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description');
            $table->json('terms');
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->string('type');
            $table->unsignedBigInteger('reward_amount');
            $table->boolean('repeatable')->default(false);
            $table->unsignedInteger('max_subscriptions')->default(1);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamp('launched_at');
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->index(['country_id', 'active', 'expired_at']);
        });

        // Preserve each explicitly requested participation attempt separately.
        Schema::create('program_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('reward_amount');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['program_id', 'user_id', 'completed_at']);
        });

        // Allow one earned reward per subscription and retain its review history.
        Schema::create('program_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_subscription_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('status');
            $table->uuid('idempotency_key')->unique();
            $table->foreignId('admin_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('review_note', 500)->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Remove program data in reverse dependency order.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_rewards');
        Schema::dropIfExists('program_subscriptions');
        Schema::dropIfExists('programs');
    }
};
