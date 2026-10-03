<section class="site-partners py-5 overflow-hidden">
    <div class="container">
        <div class="site-deals-heading text-center text-md-start mb-4">
            <h2 class="mb-1">{{ __('Our Partners in Success') }}</h2>
            <p class="text-body-secondary mb-0">
                {{ __('Progressing towards achieving more accomplishments and continuous excellence') }}
            </p>
        </div>
    </div>

    @foreach ([false, true] as $reverse)
        <div class="container @unless ($loop->last) mb-3 @endunless">
            <x-marquee :reverse="$reverse" :duration="max(20, $clients->count() * 3)">
                @foreach ($clients->shuffle() as $client)
                    <a class="site-partners-logo" href="{{ route('website.clients.show', $client) }}"
                        title="{{ $client->title }}">
                        <img src="{{ $client->logo }}" alt="{{ $client->title }}" loading="eager">
                    </a>
                @endforeach
            </x-marquee>
        </div>
    @endforeach
</section>
