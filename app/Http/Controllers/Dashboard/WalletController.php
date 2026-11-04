<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Wallets\PostWalletTransaction;
use App\Enums\WalletTransactionType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Redot\Http\Controllers\Controller;

class WalletController extends Controller
{
    public function index()
    {
        return view('dashboard.wallets.index');
    }

    public function show(User $user)
    {
        return view('dashboard.wallets.show', ['user' => $user->load('wallet')]);
    }

    public function store(Request $request, User $user, PostWalletTransaction $post)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(WalletTransactionType::class)],
            'amount' => ['required', 'string', 'regex:/\A(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?\z/'],
            'description' => ['required', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        [$whole, $fraction] = array_pad(explode('.', $validated['amount']), 2, '');
        $amount = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');

        $post->handle(
            user: $user,
            type: WalletTransactionType::from($validated['type']),
            amount: $amount,
            source: $request->user('admins'),
            description: $validated['description'],
            idempotencyKey: $validated['idempotency_key'],
            admin: $request->user('admins'),
        );

        return $this->created(__('Wallet transaction'), 'dashboard.wallets.show', $user);
    }
}
