@if ($title)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif

<div id="{{ $id }}-container">
    <select id="{{ $id }}" validation-container="#{{ $id }}-container" {{ $attributes }}>
        @foreach ($options as $key => $label)
            <option value="{{ $key }}" @selected(in_array($key, $value))>{{ $label }}</option>
        @endforeach

        {{ $slot }}
    </select>
</div>

@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif

@if ($tom)
    @pushOnce('plugins-styles', 'tom-select-styles')
        <link rel="stylesheet" href="{{ hashed_asset('/vendor/tom-select/tom-select.min.css') }}">
    @endPushOnce

    @pushOnce('plugins-scripts', 'tom-select-scripts')
        <script src="{{ hashed_asset('/vendor/tom-select/tom-select.complete.min.js') }}"></script>
    @endPushOnce
@endif
