<x-layouts::dashboard :title="__('Payout request')">
    <x-page-header class="mb-3" />

    {{-- Request identity, destination and review history --}}
    <div class="card card-body mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="card-title mb-1">{{ $payout->wallet->user->full_name }}</h2>
                <bdi dir="ltr" class="text-secondary">{{ $payout->reference }}</bdi>
            </div>
            <span class="badge bg-{{ $payout->status->color() }}-lt">{{ $payout->status->label() }}</span>
        </div>
        <dl class="row mb-0">
            <dt class="col-sm-4">{{ __('Amount') }}</dt>
            <dd class="col-sm-8"><bdi dir="ltr">{{ \App\Models\Wallet::formatAmount($payout->amount) }} {{ $payout->wallet->currency }}</bdi></dd>
            <dt class="col-sm-4">{{ __('Payout method') }}</dt>
            <dd class="col-sm-8">{{ $payout->method->label() }}</dd>
            @foreach ($payout->details as $key => $value)
                <dt class="col-sm-4">{{ __(\Illuminate\Support\Str::headline($key)) }}</dt>
                <dd class="col-sm-8 text-break"><bdi>{{ $value }}</bdi></dd>
            @endforeach
            @if ($payout->reviewed_at)
                <dt class="col-sm-4">{{ __('Reviewed by') }}</dt>
                <dd class="col-sm-8">{{ $payout->admin?->name }} — {{ $payout->reviewed_at->format('Y-m-d H:i') }}</dd>
                <dt class="col-sm-4">{{ __('Transfer reference') }}</dt>
                <dd class="col-sm-8 text-break">{{ $payout->transfer_reference ?? '-' }}</dd>
                <dt class="col-sm-4">{{ __('Review note') }}</dt>
                <dd class="col-sm-8 text-break">{{ $payout->review_note ?? '-' }}</dd>
            @endif
        </dl>
    </div>

    {{-- Payment is confirmed only after an external transfer has been made --}}
    @if ($payout->status === \App\Enums\WalletPayoutStatus::Pending && route_allowed('dashboard.wallet-payouts.update'))
        <x-form class="card card-body" :action="route('dashboard.wallet-payouts.update', $payout)" method="PUT">
            <x-alert type="info">{{ __('Only mark this request as paid after transferring the funds externally. Rejecting the request returns the reserved amount to the wallet.') }}</x-alert>
            <div class="mb-3">
                <x-select id="payout-review-status" name="status" :title="__('Decision')"
                    :options="['paid' => __('Confirm payment'), 'rejected' => __('Reject and refund')]" :value="old('status', 'paid')" :tom="false" validation="required" />
            </div>
            <div class="mb-3" visible-when="$payout-review-status == 'paid'" @style(['display: none' => old('status', 'paid') !== 'paid'])>
                <x-input name="transfer_reference" :title="__('Transfer reference')" :value="old('transfer_reference')" validation="required" maxlength="255" />
            </div>
            <div class="mb-3">
                <x-input name="review_note" :title="__('Review note')" :value="old('review_note')" maxlength="500" />
                <x-hint>{{ __('A reason is required when rejecting a payout request.') }}</x-hint>
            </div>
            <div><button type="submit" class="btn btn-primary">{{ __('Save decision') }}</button></div>
        </x-form>
    @endif
</x-layouts::dashboard>
