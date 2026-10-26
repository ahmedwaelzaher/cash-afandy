<x-layouts::website :title="$staticPage->title">
    <section class="site-static-page container mx-auto my-5 py-5">

        <div class="text-center mb-5">
            <h1 class="display-6 mb-1">
                {{ $staticPage->title }}
            </h1>

            <p class="text-muted">
                {{ __('Last updated on :date', ['date' => $staticPage->updated_at->translatedFormat('d M Y')]) }}
            </p>
        </div>

        @if ($staticPage->content)
            <div class="card card-md">
                <div class="card-body">
                    {!! $staticPage->content !!}
                </div>
            </div>
        @else
            <x-empty />
        @endif
    </section>
</x-layouts::website>
