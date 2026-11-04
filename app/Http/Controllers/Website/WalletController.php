<?php

namespace App\Http\Controllers\Website;

use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = $request->user('users')->wallet;

        return view('website.wallet.show', [
            'wallet' => $wallet,
            'transactions' => $wallet?->transactions()->latest('id')->paginate(15)->withQueryString(),
        ]);
    }
}
