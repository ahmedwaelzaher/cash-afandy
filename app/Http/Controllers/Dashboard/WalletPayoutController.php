<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Wallets\ReviewWalletPayout;
use App\Enums\WalletPayoutStatus;
use App\Models\WalletPayout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Redot\Http\Controllers\Controller;

class WalletPayoutController extends Controller
{
    /**
     * Display the payout review queue.
     */
    public function index()
    {
        return view('dashboard.wallet-payouts.index');
    }

    /**
     * Display a payout request and its destination details.
     */
    public function show(WalletPayout $walletPayout)
    {
        return view('dashboard.wallet-payouts.show', ['payout' => $walletPayout->load(['wallet.user', 'admin'])]);
    }

    /**
     * Record a completed transfer or reject and refund a payout request.
     */
    public function update(Request $request, WalletPayout $walletPayout, ReviewWalletPayout $review)
    {
        // Require a transfer reference for payment and a reason for rejection.
        $validated = $request->validate([
            'status' => ['required', Rule::in([WalletPayoutStatus::Paid->value, WalletPayoutStatus::Rejected->value])],
            'transfer_reference' => ['exclude_unless:status,paid', 'required', 'string', 'max:255'],
            'review_note' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ]);

        // Apply the decision under the same locks as wallet movements.
        $review->handle(
            $walletPayout,
            WalletPayoutStatus::from($validated['status']),
            $request->user('admins'),
            $validated['transfer_reference'] ?? null,
            $validated['review_note'] ?? null,
        );

        return $this->updated(__('Payout request'), 'dashboard.wallet-payouts.show', $walletPayout);
    }
}
