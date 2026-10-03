<x-layouts::website :title="__('Cashback')">
    <section class="site-deals py-5">
        <div class="container">
            <div class="site-deals-heading mb-4">
                <h2 class="mb-1">{{ __('Cashback Stores') }}</h2>
                <p class="text-body-secondary mb-0">
                    {{ __('Get a part of your money back on every purchase and enjoy a smarter, more rewarding shopping experience!') }}
                </p>
            </div>

            @include('website.partials.deals-filters', ['action' => route('website.cashbacks.index')])

            @if ($cashbacks->isNotEmpty())
                <div class="row row-cards">
                    @foreach ($cashbacks as $cashback)
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            @include('website.partials.cashback-card')
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $cashbacks->links() }}
                </div>
            @else
                <x-empty />
            @endif
        </div>
    </section>
</x-layouts::website>
