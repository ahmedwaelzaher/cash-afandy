<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_notification_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->dateTime('expiration_date')->nullable();
            $table->dateTime('processed_at')->nullable()->index();
            $table->unsignedBigInteger('recipient_cursor')->default(0);
            $table->timestamps();
            $table->index(['offer_id', 'type']);
            $table->index(['user_id', 'offer_id', 'type', 'expiration_date'], 'offer_notification_recipient_index');
        });

        // Establish the publication baseline without sending a backlog of existing offers.
        $baseline = now();
        DB::table('offers')->select('offers.id')->join('stores', 'stores.id', '=', 'offers.store_id')
            ->where('offers.active', true)->where('stores.active', true)->where('offers.launch_date', '<=', $baseline)
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('country_offer')->whereColumn('country_offer.offer_id', 'offers.id'))
            ->chunkById(100, function ($offers) use ($baseline) {
                $rows = $offers->map(fn ($offer) => [
                    'event_key' => 'new_offer:' . $offer->id, 'offer_id' => $offer->id,
                    'type' => 'new_offer', 'processed_at' => $baseline, 'created_at' => $baseline, 'updated_at' => $baseline,
                ])->all();
                DB::table('offer_notification_events')->insertOrIgnore($rows);
            }, 'offers.id', 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_notification_events');
    }
};
