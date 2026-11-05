<?php

namespace App\Http\Controllers\Website;

use App\Enums\WalletPayoutMethod;
use App\Enums\WalletTransactionType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Redot\Http\Controllers\Controller;

class WalletController extends Controller
{
    /**
     * Display the authenticated user's wallet and transaction history.
     */
    public function show(Request $request)
    {
        // Validate the optional transaction history filter.
        $validated = $request->validate([
            'type' => ['nullable', Rule::enum(WalletTransactionType::class)],
        ]);
        $wallet = $request->user('users')->wallet;

        // Keep the balance independent of transaction filters and pagination.
        return view('website.wallet.show', [
            'wallet' => $wallet,
            'payoutMethods' => WalletPayoutMethod::cases(),
            'transactions' => $wallet->transactions()->with('source')
                ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
                ->latest('id')->paginate(10)->withQueryString(),
        ]);
    }

    /**
     * Validate the selected payout method and reserve the requested amount.
     */
    public function storePayout(Request $request)
    {
        // Resolve the method only after validating its enum value.
        $selected = $request->validate([
            'method' => ['required', Rule::enum(WalletPayoutMethod::class)],
        ]);
        $method = WalletPayoutMethod::from($selected['method']);
        $action = app($method->action());

        // Validate the amount and only the destination fields for this method.
        $validated = $request->validate([
            'amount' => ['required', 'string', 'numeric', 'min:0.01', 'regex:/\A(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?\z/'],
            'idempotency_key' => ['required', 'uuid'],
            'payout_confirmation' => ['accepted'],
            ...$action->rules(),
        ]);

        // Convert to minor units before dispatching the selected action.
        [$whole, $fraction] = array_pad(explode('.', $validated['amount']), 2, '');
        $amount = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        $action->handle($request->user('users'), $amount, $validated['details'] ?? [], $validated['idempotency_key']);

        return $this->success(__('Your payout request has been submitted for review. The amount has been reserved from your available balance.'), 'website.wallet.show');
    }
}
