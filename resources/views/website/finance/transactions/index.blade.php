<x-layouts::website :title="__('Transactions')">
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 mb-1">{{ __('Transactions') }}</h1>
                    <p class="text-secondary mb-0">{{ __('Track every pound you earn or spend.') }}</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('website.finance.categories.index') }}" class="btn">
                        <i class="fas fa-tags me-2"></i>{{ __('Categories') }}
                    </a>

                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quick-add-transaction">
                        <i class="fas fa-plus me-2"></i>{{ __('Add Transaction') }}
                    </button>
                </div>
            </div>

            {{-- Totals of the filtered period --}}
            <div class="row row-cards mb-4">
                @foreach ([
                    ['label' => __('Income'), 'value' => $totals['income'], 'class' => 'text-success', 'icon' => 'fas fa-arrow-down'],
                    ['label' => __('Expenses'), 'value' => $totals['expense'], 'class' => 'text-danger', 'icon' => 'fas fa-arrow-up'],
                    ['label' => __('Net'), 'value' => $totals['net'], 'class' => $totals['net'] < 0 ? 'text-danger' : 'text-success', 'icon' => 'fas fa-scale-balanced'],
                ] as $card)
                    <div class="col-12 col-md-4">
                        <div class="card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <span class="avatar rounded-circle {{ $card['class'] }}">
                                    <i class="{{ $card['icon'] }}"></i>
                                </span>

                                <div>
                                    <div class="text-secondary small">{{ $card['label'] }}</div>
                                    <div class="h3 mb-0 {{ $card['class'] }}">{{ number_format($card['value'], 2) }} {{ __('EGP') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Filters (a plain form, so no CSRF token ends up in the URL) --}}
            <form class="card mb-4" action="{{ route('website.finance.transactions.index') }}" method="GET">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <x-input type="date" name="from" :title="__('From')" :value="$filters['from']" />
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3">
                            <x-input type="date" name="to" :title="__('To')" :value="$filters['to']" />
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <x-select name="type" :title="__('Type')" :tom="false"
                                :options="['' => __('All')] + \App\Enums\TransactionType::values()" :value="$filters['type'] ?? ''" />
                        </div>

                        <div class="col-12 col-sm-6 col-lg-2">
                            <x-select name="category" :title="__('Category')" :tom="false"
                                :options="['' => __('All')] + $categories->flatten()->pluck('title', 'id')->all()" :value="$filters['category'] ?? ''" />
                        </div>

                        <div class="col-12 col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">{{ __('Filter') }}</button>
                            <a href="{{ route('website.finance.transactions.index') }}" class="btn btn-icon" title="{{ __('Reset') }}">
                                <i class="fas fa-rotate-left"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            {{-- List --}}
            @if ($transactions->isEmpty())
                <x-empty icon="fas fa-receipt" :title="__('No transactions yet')"
                    :subtitle="__('Add your first income or expense to start tracking.')" />
            @else
                <div class="card">
                    <div class="list-group list-group-flush">
                        @foreach ($transactions as $transaction)
                            @php($isIncome = $transaction->type === \App\Enums\TransactionType::Income)

                            <div class="list-group-item d-flex align-items-center gap-3">
                                <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                    style="color: {{ $transaction->category->color ?? 'var(--tblr-secondary)' }}">
                                    <i class="{{ $transaction->category->icon ?: 'fas fa-tag' }}"></i>
                                </span>

                                <div class="flex-fill text-truncate">
                                    <div class="fw-medium text-truncate">{{ $transaction->category->title }}</div>
                                    <div class="text-secondary small text-truncate">
                                        {{ $transaction->occurred_on->translatedFormat('j F Y') }}
                                        @if ($transaction->note)
                                            &middot; {{ $transaction->note }}
                                        @endif
                                    </div>
                                </div>

                                <div @class(['fw-bold text-nowrap', 'text-success' => $isIncome, 'text-danger' => ! $isIncome])>
                                    {{ $isIncome ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} {{ __('EGP') }}
                                </div>

                                <div class="d-flex gap-1">
                                    <a href="{{ route('website.finance.transactions.edit', $transaction) }}"
                                        class="btn btn-sm btn-icon btn-ghost-secondary" title="{{ __('Edit') }}">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <x-form :action="route('website.finance.transactions.destroy', $transaction)" method="DELETE" finance-confirm-delete>
                                        <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="{{ __('Delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </x-form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </section>

    {{-- Quick add: amount, category and date are all it takes to log a spend --}}
    <div class="modal modal-blur fade" id="quick-add-transaction" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <x-form id="quick-add-transaction-form" class="modal-content" :action="route('website.finance.transactions.store')" method="POST">
                <div class="modal-header">
                    <h2 class="modal-title">{{ __('Add Transaction') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>

                <div class="modal-body">
                    @include('website.finance.transactions.partials.form', ['entry' => null])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                </div>
            </x-form>
        </div>
    </div>

    @push('scripts')
        <script>
            $('[finance-confirm-delete]').on('submit', function (event) {
                event.preventDefault();

                warnBeforeAction(() => this.submit());
            });

            // Reopen the quick add modal when its submission came back with errors.
            @if ($errors->any() && old('_form') === base64_encode(route('website.finance.transactions.store') . ':POST'))
                bootstrap.Modal.getOrCreateInstance('#quick-add-transaction').show();
            @endif
        </script>
    @endpush
</x-layouts::website>
