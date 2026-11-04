<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Wallets\PostWalletTransaction;
use App\Enums\WalletTransactionSource;
use App\Enums\WalletTransactionType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Redot\Http\Controllers\Controller;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.wallets.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return view('dashboard.wallets.show', ['user' => $user->load('wallet')]);
    }

    /**
     * Store a newly created wallet transaction.
     */
    public function store(Request $request, User $user, PostWalletTransaction $post)
    {
        // Validate the administrative adjustment details.
        $validated = $request->validate([
            'type' => ['required', Rule::enum(WalletTransactionType::class)],
            'amount' => ['required', 'string', 'numeric', 'min:0.01', 'regex:/\A(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?\z/'],
            'description' => ['required', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        // Convert the amount to minor units without floating-point arithmetic.
        [$whole, $fraction] = array_pad(explode('.', $validated['amount']), 2, '');
        $amount = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');

        // Post the adjustment with the authenticated administrator as its source.
        $post->handle(
            user: $user,
            type: WalletTransactionType::from($validated['type']),
            sourceCategory: WalletTransactionSource::AdminAdjustment,
            amount: $amount,
            source: $request->user('admins'),
            description: $validated['description'],
            idempotencyKey: $validated['idempotency_key'],
            admin: $request->user('admins'),
        );

        return $this->created(__('Wallet transaction'), 'dashboard.wallets.show', $user);
    }
}
