<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_website_notification_id');
            $table->foreign('scheduled_website_notification_id', 'scheduled_delivery_notification_foreign')
                ->references('id')->on('scheduled_website_notifications')->cascadeOnDelete();
            $table->string('timezone');
            $table->json('recipient_ids');
            $table->dateTime('send_at');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['scheduled_website_notification_id', 'timezone'], 'scheduled_notification_timezone_unique');
            $table->index(['sent_at', 'send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notification_deliveries');
    }
};
