@props(['variant' => 'default'])

@if ($variant === 'coin')
    <div class="page-loader site-page-loader d-flex align-items-center justify-content-center" role="progressbar" aria-label="{{ app_name() }}">
        <div class="site-loader-coin" aria-hidden="true">
            @for ($layer = 0; $layer < 17; $layer++)
                <span class="site-loader-coin-edge" style="--coin-depth: {{ ($layer - 8) * 0.5 }}px"></span>
            @endfor
            <img class="site-loader-coin-face site-loader-coin-front" src="{{ hashed_asset('assets/images/afandy-coin-front.svg') }}" alt="" width="128" height="128" loading="eager" />
            <img class="site-loader-coin-face site-loader-coin-back" src="{{ hashed_asset('assets/images/afandy-coin-back.svg') }}" alt="" width="128" height="128" loading="eager" />
        </div>
    </div>
    <script>
        (() => {
            const cover = document.currentScript.previousElementSibling;
            let dismissed = false;
            const dismiss = () => {
                if (dismissed) return;
                dismissed = true;
                clearTimeout(fallback);
                cover.classList.add('is-loaded');
                setTimeout(() => cover.remove(), 200);
            };
            // A slow third-party asset must never leave the website covered indefinitely.
            const fallback = setTimeout(dismiss, 10000);
            if (document.readyState === 'complete') {
                dismiss();
            } else {
                window.addEventListener('load', dismiss, { once: true });
            }
            window.addEventListener('pageshow', dismiss, { once: true });
        })();
    </script>
    <noscript><style>.site-page-loader { display: none !important; }</style></noscript>
@else
    <div class="page page-loader">
        <div class="container container-slim text-center py-4 my-auto">
            <div><x-logo class="mb-4" :lazy="false" /></div>
            <div class="spinner-border"></div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(window).on('load', () => $('.page-loader').fadeOut());
        </script>
    @endpush
@endif
