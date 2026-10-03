<x-layouts::website :title="__('Finance Categories')">
    <section class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 mb-1">{{ __('Finance Categories') }}</h1>
                    <p class="text-secondary mb-0">{{ __('Organize your income and expenses the way you like.') }}</p>
                </div>

                <a href="{{ route('website.finance.categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>{{ __('Add Category') }}
                </a>
            </div>

            <div class="row row-cards">
                @foreach (\App\Enums\TransactionType::cases() as $type)
                    <div class="col-12 col-lg-6">
                        <div class="card h-100">
                            <div class="card-header justify-content-between">
                                <h3 class="card-title">{{ $type->label() }}</h3>

                                <a href="{{ route('website.finance.categories.create', ['type' => $type->value]) }}"
                                    class="btn btn-sm btn-ghost-primary">
                                    <i class="fas fa-plus me-1"></i>{{ __('Add') }}
                                </a>
                            </div>

                            <div class="list-group list-group-flush">
                                @forelse ($categories->get($type->value, collect()) as $category)
                                    <div class="list-group-item d-flex align-items-center gap-3">
                                        <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                            style="color: {{ $category->color ?? 'var(--tblr-secondary)' }}">
                                            <i class="{{ $category->icon ?: 'fas fa-tag' }}"></i>
                                        </span>

                                        <span class="flex-fill text-truncate">{{ $category->title }}</span>

                                        @if ($category->isDefault())
                                            <span class="badge bg-secondary-lt">{{ __('Default') }}</span>
                                        @else
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('website.finance.categories.edit', $category) }}"
                                                    class="btn btn-sm btn-icon btn-ghost-secondary" title="{{ __('Edit') }}">
                                                    <i class="fas fa-pen"></i>
                                                </a>

                                                <x-form :action="route('website.finance.categories.destroy', $category)" method="DELETE"
                                                    finance-confirm-delete>
                                                    <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger"
                                                        title="{{ __('Delete') }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </x-form>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="list-group-item text-secondary text-center">
                                        {{ __('No categories yet.') }}
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

                warnBeforeAction(() => this.submit());
            });
        </script>
    @endpush
</x-layouts::website>
