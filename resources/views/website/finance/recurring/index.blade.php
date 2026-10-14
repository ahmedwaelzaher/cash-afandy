<x-layouts::website :title="__('Recurring Income and Expenses')">
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 mb-1">{{ __('Recurring Income and Expenses') }}</h1>
                    <p class="text-secondary mb-0">{{ __('Your salary, rent, bills and anything else that repeats, added for you on time.') }}</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('website.finance.transactions.index') }}" class="btn">
                        <i class="fas fa-receipt me-2"></i>{{ __('Transactions') }}
                    </a>

                    <a href="{{ route('website.finance.recurring.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>{{ __('Add Recurring Item') }}
                    </a>
                </div>
            </div>

            <div class="row row-cards">
                @foreach (\App\Enums\TransactionType::cases() as $type)
                    @php($items = $recurring->get($type->value, collect()))

                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header justify-content-between">
                                <h3 class="card-title">{{ $type->label() }}</h3>

                                {{-- Monthly equivalent of what is still active, to see the fixed commitments at a glance --}}
                                <span @class(['fw-bold', 'text-success' => $type === \App\Enums\TransactionType::Income, 'text-danger' => $type === \App\Enums\TransactionType::Expense])>
                                    {{ number_format($items->where('is_active', true)->whereNotNull('next_due_on')->sum(fn ($item) => $item->monthlyAmount()), 2) }}
                                    {{ __('EGP') }} / {{ __('month') }}
                                </span>
                            </div>

                            <div class="list-group list-group-flush">
                                @forelse ($items as $item)
                                    <div @class(['list-group-item d-flex align-items-center gap-3', 'opacity-50' => ! $item->is_active || ! $item->next_due_on])>
                                        <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                            style="color: {{ $item->category->color ?? 'var(--tblr-secondary)' }}">
                                            <i class="{{ $item->category->icon ?: 'fas fa-tag' }}"></i>
                                        </span>

                                        <div class="flex-fill text-truncate">
                                            <div class="fw-medium text-truncate">
                                                {{ $item->title }}
                                                <span class="badge {{ $item->nature === \App\Enums\RecurrenceNature::Fixed ? 'bg-secondary-lt' : 'bg-warning-lt' }} ms-1">
                                                    {{ $item->nature->label() }}
                                                </span>
                                            </div>

                                            <div class="text-secondary small text-truncate">
                                                {{ $item->frequency->label() }} &middot;
                                                @if (! $item->is_active)
                                                    {{ __('Paused') }}
                                                @elseif ($item->next_due_on)
                                                    {{ __('Next on :date', ['date' => $item->next_due_on->translatedFormat('j F Y')]) }}
                                                @else
                                                    {{ __('Ended') }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="fw-bold text-nowrap">
                                            {{ number_format($item->amount, 2) }} {{ __('EGP') }}
                                        </div>

                                        <div class="d-flex gap-1">
                                            <a href="{{ route('website.finance.recurring.edit', $item) }}"
                                                class="btn btn-sm btn-icon btn-ghost-secondary" title="{{ __('Edit') }}">
                                                <i class="fas fa-pen"></i>
                                            </a>

                                            <x-form :action="route('website.finance.recurring.destroy', $item)" method="DELETE" finance-confirm-delete>
                                                <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="{{ __('Delete') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </x-form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="list-group-item text-secondary text-center">
                                        {{ __('Nothing recurring yet.') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            $('[finance-confirm-delete]').on('submit', function (event) {
                event.preventDefault();

                warnBeforeAction(() => this.submit(), {
                    content: @json(__('Transactions already added from it will stay.')),
                });
            });
        </script>
    @endpush
</x-layouts::website>
