<div class="site-deals-card card h-100">
    <div class="site-deals-image">
        <span class="site-deals-badge">
            {{ $coupon->fixed_discount ? $coupon->discount . ' ' . __('EGP') : $coupon->discount . '%' }}
        </span>
    </div>

    <span class="site-deals-logo">
        <img src="{{ $coupon->client->logo }}" alt="{{ $coupon->client->title }}" loading="lazy">
    </span>

    <div class="card-body text-center pt-3">
        <h4 class="site-deals-title mb-2">{{ $coupon->title }}</h4>
        <p class="site-deals-content text-body-secondary mb-3">
            {{ strip_tags($coupon->description) }}
        </p>
        <a href="{{ route('website.coupons.show', $coupon) }}" class="btn btn-outline-brand brand-btn">
            {{ __('Get it now') }}
        </a>
    </div>
</div>
