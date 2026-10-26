<x-layouts::website :title="$staticPage->title">
    <section class="site-static-page py-4 py-md-5" aria-labelledby="static-page-title">
        <div class="container site-container">
            <header class="mb-4">
                <h1 class="display-5 fw-bold mb-2" id="static-page-title">{{ $staticPage->title }}</h1>
                @if ($staticPage->updated_at)
                    <p class="text-secondary mb-0">
                        {{ __('Last updated on :date', ['date' => $staticPage->updated_at->translatedFormat('d M Y')]) }}
                    </p>
                @endif
            </header>

            @if ($staticPage->content)
                <article class="site-static-article card">
                    <div class="site-static-body card-body p-4 p-md-5">
                        {!! $staticPage->content !!}
                    </div>
                </article>
            @else
                <x-empty :icon="null" class="rounded-4" />
            @endif
        </div>
    </section>
</x-layouts::website>
