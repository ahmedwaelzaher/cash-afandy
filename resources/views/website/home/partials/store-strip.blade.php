<section class="site-store-strip" aria-labelledby="store-strip-title">
    <div class="container site-container site-store-strip-content">
        <h2 id="store-strip-title">
            {{ __('Your favorite stores.') }}
            <strong class="d-inline d-md-block">{{ __('Better savings.') }}</strong>
        </h2>

        <x-marquee :duration="50" :reverse="true">
            @foreach ($stores as $store)
                <a class="site-store-logo flex-center" href="{{ route('website.clients.show', $store['slug']) }}">
                    <img src="{{ $store['logo'] }}" alt="{{ $store['name'] }}" width="120" height="40" loading="lazy" referrerpolicy="no-referrer">
                </a>
            @endforeach
        </x-marquee>

        <a href="{{ route('website.clients.index') }}" class="d-inline-flex align-items-center gap-2 text-nowrap">
            {{ __('All stores') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" />
        </a>
    </div>
</section>
