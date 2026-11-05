@php($selectedMethod = old('method'))

@if (empty($payoutMethods))
    <div><span class="badge bg-primary-lt fs-5 fw-medium px-3 py-2">{{ __('Coming soon') }}</span></div>
@elseif ($wallet->balance < 1)
    <x-alert type="info" class="mb-0">{{ __('You need an available balance to request a payout.') }}</x-alert>
@else
    <x-form :action="route('website.wallet.payouts.store')" method="POST">
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}" />

        {{-- Available methods come from the payout enum --}}
        <div class="mb-3">
            <fieldset>
                <legend class="form-label">{{ __('Payout method') }}</legend>
                <div class="form-selectgroup d-flex flex-wrap" id="wallet-payout-method">
                    @foreach ($payoutMethods as $method)
                        <label class="form-selectgroup-item flex-fill">
                            <input type="radio" name="method" value="{{ $method->value }}" class="form-selectgroup-input" @checked($selectedMethod === $method->value)
                                @if ($loop->first) validation="required" validation-container="#wallet-payout-method" @endif />
                            <span class="form-selectgroup-label d-flex align-items-center justify-content-center gap-2">
                                <x-icon :icon="$method->icon()" class="fs-2 m-0" />{{ $method->label() }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </div>

        {{-- Confirm the amount before reserving funds for review --}}
        <div class="mb-3">
            <x-input name="amount" :title="__('Payout amount')" :append="$wallet->currency" :value="old('amount')" inputmode="decimal" dir="ltr" validation="required" maxlength="13" />
            <x-hint class="mt-2">{{ __('Maximum available: :amount :currency', ['amount' => \App\Models\Wallet::formatAmount($wallet->balance), 'currency' => $wallet->currency]) }}</x-hint>
        </div>
        <div class="border-top pt-3 mt-3">
            <p class="text-secondary small">{{ __('The requested amount is reserved until an administrator reviews your payout. Rejected requests are refunded to your available balance.') }}</p>
            <x-checkboxes name="payout_confirmation" :options="[1 => __('I have checked my payout details and confirm this request.')]" :value="old('payout_confirmation')" validation="required|accepted" />
            <button type="submit" class="btn btn-primary w-100 mt-3">{{ __('Confirm payout request') }}</button>
        </div>
    </x-form>
@endif
