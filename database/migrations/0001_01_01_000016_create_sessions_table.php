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
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('session_authentications', function (Blueprint $table) {
            $table->string('session_id');
            $table->string('guard');
            $table->morphs('user');

            $table->foreign('session_id')->references('id')->on('sessions')->cascadeOnDelete();
            $table->unique(['session_id', 'guard']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_authentications');
        Schema::dropIfExists('sessions');
    }
};
