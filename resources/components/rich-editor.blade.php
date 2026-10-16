@include('components.textarea')

@pushOnce('plugins-scripts', 'hugerte-scripts')
    <script src="{{ hashed_asset('/vendor/hugerte/hugerte.min.js') }}"></script>
@endPushOnce
