<?php

namespace App\Http\Controllers\Website;

use App\Models\Cashback;
use App\Models\Client;
use App\Models\Coupon;
use Redot\Http\Controllers\Controller;

class ClientController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        $country = website_country();

        abort_unless($client->active, 404);
        abort_if($country && ! $client->countries()->whereKey($country->id)->exists(), 404);

        $client->load('categories');

        return view('website.clients.show', [
            'client' => $client,
            'coupons' => Coupon::available()->with('client')->whereBelongsTo($client)->latest('id')->get(),
            'cashbacks' => Cashback::available()->with('client')->whereBelongsTo($client)->latest('id')->get(),
        ]);
    }
}
