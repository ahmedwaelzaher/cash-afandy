@pushOnce('plugins-styles', 'intl-tel-input-styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@29.1.0/dist/css/intlTelInput.min.css" />
@endPushOnce

@if ($title)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif

<div class="phone-control">
    <input type="tel" id="{{ $id }}" {{ $attributes->class(['form-control']) }} />
</div>

@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif

@pushOnce('plugins-scripts', 'intl-tel-input-scripts')
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@29.1.0/dist/js/intlTelInput.min.js"></script>
@endPushOnce
