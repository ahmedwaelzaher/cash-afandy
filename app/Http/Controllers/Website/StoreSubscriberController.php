<?php

namespace App\Http\Controllers\Website;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class StoreSubscriberController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        Subscriber::firstOrCreate(['email' => $validated['email']]);

        toastify()->success(__('You have successfully subscribed to our newsletter 🎉'));

        return redirect()->back();
    }
}
