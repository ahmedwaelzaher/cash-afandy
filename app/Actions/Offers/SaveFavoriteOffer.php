<?php

namespace App\Actions\Offers;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveFavoriteOffer
{
    public function handle(User $user, Offer $offer): void
    {
        // Keep retries idempotent, including requests from multiple tabs.
        DB::table('offer_user')->insertOrIgnore([
            'user_id' => $user->id,
            'offer_id' => $offer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
