@php
    $maskedCode = str_repeat('*', max(0, mb_strlen($coupon->code) - 2)) . mb_substr($coupon->code, -2);
@endphp

<div class="site-offer-code d-flex align-items-stretch">
    <bdi class="d-flex align-items-center text-break px-3" dir="ltr">{{ $maskedCode }}</bdi>
    <button type="button" class="btn btn-primary flex-shrink-0 gap-2 rounded-0" data-coupon-code="{{ encode_coupon_code($coupon->code) }}"
        data-coupon-title="{{ $coupon->title }}" data-store-name="{{ $coupon->client->title }}"
        data-store-logo="{{ $coupon->client->logo }}" data-store-url="{{ $coupon->client->url }}"
        data-bs-toggle="modal" data-bs-target="#site-coupon-reveal">{{ __('Copy code') }} <x-icon icon="ti ti-copy" /></button>
</div>

@include('website.partials.coupon-reveal')
