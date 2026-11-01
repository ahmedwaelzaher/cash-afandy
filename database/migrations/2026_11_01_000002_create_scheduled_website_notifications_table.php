<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_website_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->text('url')->nullable();
            $table->json('recipient_ids');
            $table->dateTime('send_at');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->index(['sent_at', 'send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_website_notifications');
    }
};
