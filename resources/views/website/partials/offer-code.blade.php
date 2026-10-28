@php
    $maskedCode = str_repeat('*', max(0, mb_strlen($offer->code) - 2)) . mb_substr($offer->code, -2);
@endphp

<div class="site-offer-code d-flex align-items-stretch">
    <bdi class="d-flex align-items-center text-break px-3" dir="ltr">{{ $maskedCode }}</bdi>
    <button type="button" class="btn btn-primary flex-shrink-0 gap-2 rounded-0" data-offer-code="{{ encode_offer_code($offer->code) }}"
        data-offer-title="{{ $offer->title }}" data-store-name="{{ $offer->store->title }}"
        data-store-logo="{{ $offer->store->logo }}" data-store-url="{{ $offer->store->url }}"
        data-bs-toggle="modal" data-bs-target="#site-offer-reveal">{{ __('Copy code') }} <x-icon icon="ti ti-copy" /></button>
</div>

@include('website.partials.offer-reveal')
