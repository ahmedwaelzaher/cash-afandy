<x-layouts::website :title="__('Balance')">
    <section class="site-static-page py-4" aria-labelledby="wallet-title">
        <div class="container site-container">
            {{-- Earnings page heading --}}
            <header class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h1 class="display-6 fw-bold mb-2" id="wallet-title">{{ __('Balance') }}</h1>
                    <p class="text-secondary mb-0">{{ __('Track your earnings and withdraw your balance in one click.') }}</p>
                </div>
            </header>

            <x-status class="mb-3" />

            <div class="row g-4 align-items-start">
                {{-- Compact balance and payout cards share the account sidebar pattern --}}
                <aside class="col-12 col-lg-4" aria-labelledby="wallet-balance-title">
                    <div class="d-flex flex-column gap-3" @style(['--tblr-primary-lt: var(--site-soft)'])>
                        <div class="card card-body bg-primary-lt p-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <x-icon icon="ti ti-wallet" class="d-inline-flex align-items-center justify-content-center fs-1 m-0 lh-1 w-auto h-auto flex-shrink-0" />
                                <h2 class="h3 mb-0 lh-1" id="wallet-balance-title">{{ __('Balance') }}</h2>
                            </div>
                            <div class="text-break">
                                <span class="d-inline-flex flex-wrap align-items-baseline gap-2 mw-100 display-6 fw-bold">
                                    <bdi dir="ltr">{{ \App\Models\Wallet::formatAmount($wallet->balance) }}</bdi>
                                    <bdi dir="ltr">{{ $wallet->currency }}</bdi>
                                </span>
                            </div>
                        </div>

                        <section class="card card-body p-4" aria-labelledby="wallet-payout-title">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <x-icon icon="ti ti-cash-banknote" class="d-inline-flex align-items-center justify-content-center fs-1 m-0 lh-1 w-auto h-auto flex-shrink-0" />
                                <h2 class="h3 mb-0 lh-1" id="wallet-payout-title">{{ __('Withdraw balance') }}</h2>
                            </div>
                            @if (! empty($payoutMethods))
                                <p class="text-secondary mb-4">{{ __('Choose a payout method and confirm your details.') }}</p>
                            @endif
                            @include('website.wallet.partials.payout')
                        </section>
                    </div>
                </aside>

                {{-- Paginated transaction history scoped to this wallet --}}
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header flex-wrap gap-3 justify-content-between">
                            <h2 class="card-title h2 mb-0">{{ __('Transactions') }}</h2>
                            <form action="{{ route('website.wallet.show') }}" method="GET" class="d-flex align-items-center gap-2">
                                <x-select id="wallet-history-type" name="type" :title="null" :options="['' => __('All transactions')] + \App\Enums\WalletTransactionType::values()"
                                    :value="request('type', '')" :tom="false" :aria-label="__('Filter transactions')" />
                                <button type="submit" class="btn btn-icon" aria-label="{{ __('Filter') }}"><x-icon icon="ti ti-filter" /></button>
                            </form>
                        </div>
                        @if ($transactions->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Created At') }}</th>
                                            <th scope="col">{{ __('Details') }}</th>
                                            <th scope="col">{{ __('Status') }}</th>
                                            <th scope="col">{{ __('Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            <tr>
                                                <td class="text-nowrap">{{ $transaction->created_at->translatedFormat('d M Y') }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $transaction->source_label }}</div>
                                                    <div class="small text-secondary text-break">{{ $transaction->description }}</div>
                                                    <bdi dir="ltr" class="small text-secondary">{{ $transaction->reference }}</bdi>
                                                </td>
                                                <td class="text-nowrap">
                                                    @if ($transaction->source_category === \App\Enums\WalletTransactionSource::PayoutRequest)
                                                        <span class="badge bg-{{ $transaction->source->status->color() }}-lt">{{ $transaction->source->status->label() }}</span>
                                                    @else
                                                        <span class="badge bg-green-lt">{{ __('Completed') }}</span>
                                                    @endif
                                                </td>
                                                <td @class(['text-nowrap fw-semibold', 'text-green' => $transaction->type === \App\Enums\WalletTransactionType::Credit, 'text-red' => $transaction->type === \App\Enums\WalletTransactionType::Debit])>
                                                    <bdi dir="ltr">{{ $transaction->type === \App\Enums\WalletTransactionType::Credit ? '+' : '-' }}{{ \App\Models\Wallet::formatAmount($transaction->amount) }} {{ $wallet->currency }}</bdi>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="card-body py-5"><x-empty icon="ti ti-wallet" :title="__('No wallet transactions yet')" :subtitle="null" /></div>
                        @endif
                        @if ($transactions->hasPages())
                            <div class="card-footer">{{ $transactions->onEachSide(1)->links('components.pagination') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::website>
