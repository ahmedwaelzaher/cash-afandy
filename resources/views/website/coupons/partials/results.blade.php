@if ($coupons->isNotEmpty())
    <div class="site-offers-grid">
        @foreach ($coupons as $coupon)
            @php($category = $coupon->client->categories->firstWhere('slug', $categorySlug) ?? $coupon->client->categories->first())
            <div>
                @include('website.partials.coupon-card', [
                    'categoryLabel' => $category?->title,
                    'categorySlug' => $category?->slug,
                ])
            </div>
        @endforeach
    </div>
    @if ($coupons->hasPages())
        <div class="mt-4" data-directory-pagination>{{ $coupons->links() }}</div>
    @endif
@else
    <x-empty icon="ti ti-ticket" :title="__('No results found')" :subtitle="__('Try searching with a different keyword')" class="site-store-empty rounded-4 justify-content-center" />
@endif
