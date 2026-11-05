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
        Schema::create('wallet_payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('method');
            $table->unsignedBigInteger('amount');
            $table->text('details');
            $table->string('status');
            $table->uuid('idempotency_key');
            $table->foreignId('admin_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('transfer_reference')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Identify repeated submissions within the same wallet.
            $table->unique(['wallet_id', 'idempotency_key']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_payouts');
    }
};
