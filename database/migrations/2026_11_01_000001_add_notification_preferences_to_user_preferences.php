<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->boolean('notifications_enabled')->default(true);
            $table->json('notification_types')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn(['notifications_enabled', 'notification_types']);
        });
    }
};
