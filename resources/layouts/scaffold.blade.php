<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $direction }}" data-default-locale="{{ setting('default_locale') }}">

<head>
    <base href="{{ url('/') }}" />

    <meta charset="UTF-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <meta name="title" content="{!! $title !!}" />
    <meta name="robots" content="index, follow" />
    <meta name="author" content="Abdelrhman Said" />

    @stack('meta')

    <title>{!! $title !!}</title>

    <link rel="icon" href="{{ hashed_asset('/favicon.ico') }}" type="image/x-icon" />

    {{-- Caching using Service Worker --}}
    @if (config('app.env') === 'production' && setting('service_worker_enabled'))
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/service-worker.js');
                });
            }
        </script>
    @else
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(e => e.forEach(e => e.unregister()));
                caches.keys().then(e => e.forEach(e => caches.delete(e)));
            }
        </script>
    @endif

    <link rel="stylesheet" href="{{ hashed_asset("/vendor/tabler/css/tabler.$direction.min.css") }}" />
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/jquery-confirm/jquery-confirm.min.css') }}" />
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/fancybox/fancybox.min.css') }}" />

    <link rel="stylesheet" href="{{ hashed_asset('/vendor/tabler-icons/tabler-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/glare/glare.min.css') }}" />

    @stack('plugins-styles')

    <link rel="stylesheet" href="{{ hashed_asset('/vendor/tabler/css/tabler-vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ hashed_asset('/assets/css/app.css') }}" />
    <link rel="stylesheet" href="{{ hashed_asset('/assets/css/overrides.css') }}" />

    @stack('styles')
</head>

<body {{ $attributes->class(['antialiased']) }}>
    <script>
        (() => {
            if (window.top === window.self) return;

            let parent = window.parent.document.body;
            let hasFancyboxActive = parent.classList.contains('fancybox-active') || !!parent.querySelector('.glare-container');

            if (hasFancyboxActive) {
                document.body.classList.add('inline-layout');
                document.body.classList.remove('standard-layout');
            }
        })();
    </script>

    @stack('pre-content')

    {{ $slot }}

    @stack('templates')

    {{-- Core --}}
    <script src="{{ hashed_asset('/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/lodash/lodash.min.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/tabler/js/tabler.min.js') }}"></script>
    <script src="{{ hashed_asset('/assets/js/functions.js') }}"></script>

    {{-- Plugins --}}
    <script src="{{ hashed_asset('/vendor/jquery-confirm/jquery-confirm.min.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/fancybox/fancybox.min.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/bootbox/bootbox.min.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/glare/glare.min.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/sortable/sortable.min.js') }}"></script>
    <script src="{{ hashed_asset('/assets/plugins/redot-visibility.js') }}"></script>
    <script src="{{ hashed_asset('/assets/plugins/redot-validator.js') }}"></script>
    <script src="{{ hashed_asset('/assets/plugins/redot-validator-rules.js') }}"></script>

    {{-- Language Script --}}
    <script src="{{ hashed_asset('/assets/dist/translations/' . app()->getLocale() . '.js') }}"></script>

    {{-- Custom Scripts --}}
    <script src="{{ hashed_asset('/assets/dist/init.js') }}"></script>
    <script src="{{ hashed_asset('/assets/js/app.js') }}"></script>

    <x-toasts />

    <script>
        window.OldBag = _.toPlainObject(@json(old()));
        window.ErrorsBag = _.toPlainObject(@json(isset($errors) ? $errors->getMessages() : []));
    </script>

    @if (isset($errors) && $errors->any())
        <script>
            $(document).ready(() => {
                if (window.OldBag.hasOwnProperty('_form')) {
                    $form = $(`[name="_form"][value="${window.OldBag._form}"`).closest('form');
                } else {
                    $form = $('body');
                }

                if ($form.hasAttr('toast-errors')) {
                    Object.values(window.ErrorsBag).forEach((messages) => toastify().error(messages[0]));
                    markInvalidInputs($form, window.ErrorsBag);
                } else {
                    appendErrorsToForm($form, window.ErrorsBag);
                    scrollToFirstError($form);
                }
            });
        </script>
    @endif

    @stack('plugins-scripts')
    @stack('scripts')
</body>

</html>
