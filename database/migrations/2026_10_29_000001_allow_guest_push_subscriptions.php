<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->string('notifiable_type')->nullable()->change();
            $table->unsignedBigInteger('notifiable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Guest subscriptions must be handled explicitly before restoring required ownership.
        if (DB::table('push_subscriptions')->whereNull('notifiable_type')->orWhereNull('notifiable_id')->exists()) {
            throw new RuntimeException('Guest push subscriptions must be handled before rolling back.');
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->string('notifiable_type')->nullable(false)->change();
            $table->unsignedBigInteger('notifiable_id')->nullable(false)->change();
        });
    }
};
