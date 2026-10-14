<?php

use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceNature;
use App\Enums\TransactionType;
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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finance_category_id')->constrained()->cascadeOnDelete();
            $table->enum('type', TransactionType::cases());
            $table->enum('nature', RecurrenceNature::cases());
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->enum('frequency', RecurrenceFrequency::cases());
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('next_due_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'next_due_on']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('recurring_transaction_id')->nullable()->after('finance_category_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_transaction_id');
        });

        Schema::dropIfExists('recurring_transactions');
    }
};
