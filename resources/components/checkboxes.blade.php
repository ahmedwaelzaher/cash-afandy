@if ($title)
    <x-label :title="$title" :for="$id" :required="$required" />
@endif

<div id="{{ $id }}">
    @foreach ($options as $key => $label)
        <label @class(['form-check', 'form-check-inline' => $inline])>
            <input type="checkbox" class="form-check-input" name="{{ $name }}" value="{{ $key }}"
                @checked(in_array($key, $value)) @disabled(in_array($key, $disabled))
                @if ($validation && $loop->first) validation="{{ $validation }}" validation-container="#{{ $id }}" @endif
                title="{{ $label instanceof \Illuminate\Contracts\Support\Htmlable ? strip_tags($label->toHtml()) : $label }}" />
            <span class="form-check-label">{{ $label }}</span>
        </label>
    @endforeach
</div>

@if ($hint)
    <x-hint class="mt-1">{{ $hint }}</x-hint>
@endif
