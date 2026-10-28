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
        Schema::create('cashbacks', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->float('percentage');
            $table->json('details');
            $table->foreignId('store_id')->constrained();
            $table->foreignId('representative_id')->constrained();
            $table->foreignId('country_id')->constrained();
            $table->json('terms')->nullable();
            $table->json('how_it_works')->nullable();
            $table->integer('verification_period')->nullable();
            $table->json('tips')->nullable();
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
        Schema::dropIfExists('cashbacks');
    }
};
