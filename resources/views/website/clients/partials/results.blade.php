@if ($stores->isNotEmpty())
    <div class="site-stores-grid">
        @foreach ($stores as $store)
            @include('website.partials.store-card')
        @endforeach
    </div>
    @if ($stores->hasPages())
        <div class="mt-4" data-store-pagination>{{ $stores->links() }}</div>
    @endif
@else
    <x-empty icon="ti ti-building-store" :title="__('No results found')" :subtitle="__('Try searching with a different keyword')" class="site-store-empty rounded-4 justify-content-center" />
@endif
