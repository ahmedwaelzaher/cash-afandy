@pushOnce('plugins-styles', 'query-builder-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/query-builder/query-builder.min.css') }}">
@endPushOnce

@pushOnce('plugins-styles', 'tom-select-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/tom-select/tom-select.min.css') }}">
@endPushOnce

@pushOnce('plugins-styles', 'tempus-dominus-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/tempus-dominus/tempus-dominus.min.css') }}">
@endPushOnce

@if ($title)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif

<div query-builder-container>
    <input type="hidden" id="{{ $id }}" value="{{ $value }}" {{ $attributes->merge(['init' => 'query-builder']) }} />
    <div query-builder></div>
</div>

@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif

@pushOnce('plugins-scripts', 'popper-scripts')
    <script src="{{ hashed_asset('/vendor/popper/popper.min.js') }}"></script>
@endPushOnce

@pushOnce('plugins-scripts', 'tempus-dominus-scripts')
    <script src="{{ hashed_asset('/vendor/tempus-dominus/tempus-dominus.min.js') }}"></script>
@endPushOnce

@pushOnce('plugins-scripts', 'tom-select-scripts')
    <script src="{{ hashed_asset('/vendor/tom-select/tom-select.complete.min.js') }}"></script>
@endPushOnce

@pushOnce('plugins-scripts', 'query-builder-scripts')
    <script src="{{ hashed_asset('/vendor/query-builder/query-builder.compatibility.js') }}"></script>
    <script src="{{ hashed_asset('/vendor/query-builder/query-builder.min.js') }}"></script>
@endPushOnce
