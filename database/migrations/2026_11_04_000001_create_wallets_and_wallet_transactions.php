<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Store one wallet and its balance for each user.
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->unsignedBigInteger('balance')->default(0);
            $table->timestamps();
        });

        // Store wallet movements with their source and request reference.
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->string('source_category');
            $table->morphs('source');
            $table->foreignId('admin_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description', 500);
            $table->uuid('idempotency_key');
            $table->timestamp('created_at');

            // Prevent duplicate requests within the same wallet.
            $table->unique(['wallet_id', 'idempotency_key']);
            $table->index(['wallet_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
