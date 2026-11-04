<x-layouts::dashboard :title="__('Afandy Wallet')">
    <x-page-header class="mb-3" />
    <div class="card card-body mb-3">
        <h2 class="card-title">{{ $user->full_name }}</h2>
        <div class="subheader">{{ __('Available balance') }}</div>
        <div class="h1 mb-0"><bdi dir="ltr">{{ \App\Models\Wallet::formatAmount($user->wallet?->balance ?? 0) }} {{ $user->wallet?->currency ?? '-' }}</bdi></div>
    </div>

    @if (! $user->wallet)
        <x-alert type="info" class="mb-3">{{ __('The user must confirm their account country before using the wallet.') }}</x-alert>
    @endif

    @if ($user->wallet && ! $user->trashed() && route_allowed('dashboard.wallets.store'))
        <x-form class="card mb-3" :action="route('dashboard.wallets.store', $user)" method="POST">
            <div class="card-header"><h2 class="card-title">{{ __('Wallet transaction') }}</h2></div>
            <div class="card-body">
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}" />
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <x-select name="type" :title="__('Type')" :options="\App\Enums\WalletTransactionType::values()" :value="old('type', 'credit')" :tom="false" validation="required" />
                    </div>
                    <div class="col-12 col-md-6">
                        <x-input name="amount" :title="__('Amount')" :append="$user->wallet->currency" inputmode="decimal" dir="ltr" validation="required" maxlength="13" />
                    </div>
                    <div class="col-12">
                        <x-input name="description" :title="__('Reason')" validation="required" maxlength="500" />
                    </div>
                </div>
            </div>
            <div class="card-footer text-end"><button type="submit" class="btn btn-primary">{{ __('Save') }}</button></div>
        </x-form>
    @endif

    <livewire:datatables.wallet-transactions :user-id="$user->id" />
</x-layouts::dashboard>
