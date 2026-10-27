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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('code')->unique();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('representative_id')->constrained();
            $table->json('description')->nullable();
            $table->json('tips')->nullable();
            $table->boolean('fixed_discount')->default(false);
            $table->float('discount');
            $table->float('minimum_amount')->nullable();
            $table->float('maximum_amount')->nullable();
            $table->integer('minimum_usages')->nullable();
            $table->integer('maximum_usages')->nullable();
            $table->timestamp('launch_date');
            $table->timestamp('expiration_date')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
