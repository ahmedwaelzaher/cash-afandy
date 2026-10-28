@php
    $detailsId = 'offer-details-' . ($offer->getKey() ?? \Illuminate\Support\Str::uuid());
    $offerUrl = $offer->url ?? null;
@endphp
<article class="site-offer-card card h-100">
    <div class="site-offer-discount d-flex flex-row-reverse flex-sm-column align-items-center justify-content-center gap-2 text-center">
        <strong dir="ltr">{{ $offer->discount }}{{ $offer->fixed_discount ? ' ' . __('EGP') : '%' }}</strong>
        <span>{{ __('Discount') }}</span>
    </div>
    <div class="card-body d-flex flex-column p-3">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <a href="{{ route('website.stores.show', $offer->store) }}" class="site-offer-store d-flex align-items-center gap-2 fw-semibold text-reset text-decoration-none">
                <span class="site-offer-icon flex-center flex-shrink-0">
                    @if ($offer->store->logo)
                        <img class="object-fit-contain" src="{{ $offer->store->logo }}" alt="" width="32" height="32" loading="lazy" referrerpolicy="no-referrer">
                    @else
                        <x-icon :icon="$icon ?? 'ti ti-shopping-bag'" />
                    @endif
                </span>
                {{ $offer->store->title }}
            </a>
            <div class="d-flex align-items-center flex-shrink-0">
                <button type="button" class="btn btn-icon btn-link text-secondary text-decoration-none" data-share-url="{{ route('website.index') }}"
                    data-share-title="{{ __('Cash Afandy') }}" data-share-success="{{ __('Link copied') }}"
                    data-share-error="{{ __('Unable to share or copy the link. Please try again.') }}" aria-label="{{ __('Share') }}">
                    <x-icon icon="ti ti-share" />
                </button>
                <button type="button" class="site-offer-favorite btn btn-icon btn-link text-decoration-none" data-offer-favorite aria-pressed="false" aria-label="{{ __('Favorite') }}">
                    <x-icon icon="ti ti-heart" />
                </button>
            </div>
        </div>

        <div class="site-offer-summary">
            <h3 class="mb-0">{{ $offer->title }}</h3>
        </div>

        <div class="site-offer-actions d-flex flex-wrap flex-sm-column align-items-center align-items-sm-stretch justify-content-between gap-2">
            @isset($categoryLabel)
                <a href="{{ route('website.stores.index', ['category' => $categorySlug ?? null]) }}" class="site-offer-label align-self-sm-start text-decoration-none">{{ $categoryLabel }}</a>
            @endisset

            <div class="site-offer-footer d-sm-flex flex-wrap align-items-center justify-content-between gap-3">
                @if (filled($offer->code))
                    @include('website.partials.offer-code')
                @elseif (filled($offerUrl))
                    <a href="{{ $offerUrl }}" class="site-offer-link btn btn-outline-primary gap-2" target="_blank" rel="sponsored noopener noreferrer">
                        {{ __('View offer') }} <x-icon icon="ti ti-external-link" />
                    </a>
                @endif
                <button type="button" class="site-offer-details btn btn-link d-inline-flex align-items-center gap-2 text-decoration-none p-0" data-bs-toggle="modal" data-bs-target="#{{ $detailsId }}">{{ __('Details') }} <x-icon icon="ti ti-arrow-left" class="site-menu-arrow" /></button>
            </div>
        </div>
    </div>
</article>

<div class="modal fade" id="{{ $detailsId }}" tabindex="-1" aria-labelledby="{{ $detailsId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 p-4 pb-3">
                <div class="d-flex align-items-center gap-2 text-secondary fw-semibold">
                    @if ($offer->store->logo)
                        <span class="site-offer-icon flex-center flex-shrink-0">
                            <img class="object-fit-contain" src="{{ $offer->store->logo }}" alt="" width="32" height="32" loading="lazy" referrerpolicy="no-referrer">
                        </span>
                    @endif
                    {{ $offer->store->title }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <h3 class="h2 mb-3" id="{{ $detailsId }}-title">{{ $offer->title }}</h3>
                <ul class="text-secondary lh-lg mb-0">
                    @if ($offer->description)
                        <li>{{ strip_tags($offer->description) }}</li>
                    @endif
                    @foreach (preg_split('/\R/u', strip_tags($offer->tips ?? ''), flags: PREG_SPLIT_NO_EMPTY) as $tip)
                        <li>{{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
