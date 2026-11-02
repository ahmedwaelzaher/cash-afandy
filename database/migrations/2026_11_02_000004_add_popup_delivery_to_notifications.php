<?php

use App\Notifications\WebsiteNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dateTime('popup_shown_at')->nullable();
            $table->uuid('popup_claim_token')->nullable();
            $table->dateTime('popup_claim_expires_at')->nullable();
            $table->index(['notifiable_type', 'notifiable_id', 'popup_shown_at'], 'notifications_popup_recipient_index');
        });

        // Existing notifications have no trustworthy popup history; start tracking new ones only.
        DB::table('notifications')->where('type', WebsiteNotification::class)
            ->update(['popup_shown_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_popup_recipient_index');
            $table->dropColumn(['popup_shown_at', 'popup_claim_token', 'popup_claim_expires_at']);
        });
    }
};
