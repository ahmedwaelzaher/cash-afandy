@if ($title)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif

<div @class([
    'col',
    'w-100',
    'input-group' => $isInputGroup,
    'input-group-flat' => $isInputGroup && $flat,
])>
    @if ($prepend)
        <span class="input-group-text">{{ $prepend }}</span>
    @endif

    <input type="{{ $type }}" id="{{ $id }}" {{ $attributes->class(['form-control']) }} />

    @if ($isPassword)
        <button type="button" class="input-group-text" tabindex="-1" onclick="togglePasswordField(this)">
            <i class="fas fa-eye"></i>
        </button>
    @endif

    @if ($append)
        <span class="input-group-text">{{ $append }}</span>
    @endif
</div>

@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif

@if ($isPassword)
    @pushOnce('scripts')
        <script>
            function togglePasswordField(button) {
                let $wrapper = $(button).closest('.input-group');
                let $input = $wrapper.find('input');
                let $icon = $wrapper.find('i');

                // Determine if the input is password or text
                let isPassword = $input.attr('type') === 'password';

                $input.attr('type', isPassword ? 'text' : 'password');
                $icon.toggleClass('fa-eye fa-eye-slash');
            }
        </script>
    @endPushOnce
@endif
