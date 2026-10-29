<div class="site-deals-card card h-100">
    <div class="site-deals-image">
        <span class="site-deals-badge" dir="ltr">{{ $cashback->percentage }}%</span>
    </div>

    <span class="site-deals-logo">
        <img src="{{ $cashback->store->logo }}" alt="{{ $cashback->store->title }}" loading="lazy">
    </span>

    <div class="card-body d-flex flex-column flex-fill text-center pt-3">
        <h4 class="site-deals-title mb-2">{{ $cashback->store->title }}</h4>
        <p class="site-deals-content text-body-secondary mb-3">
            {{ strip_tags($cashback->store->description) }}
        </p>
        <a href="{{ route('website.cashbacks.show', $cashback) }}" class="btn btn-outline-brand brand-btn">
            {{ __('Get it now') }}
        </a>
    </div>
</div>
