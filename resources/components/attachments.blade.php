@if ($title)
    <x-label :title="$title" :for="$id" />
@endif

<div attachments-list>
    @foreach ($attachments as $attachment)
        <a href="{{ $attachment['url'] }}" class="uploader-item text-decoration-none" download>
            <div class="uploader-item-preview">
                @if (str_starts_with($attachment['type'], 'image/'))
                    <img class="uploader-item-image" src="{{ $attachment['thumbnail'] ?? $attachment['url'] }}" alt="{{ $attachment['name'] }}">
                @else
                    <x-icon :icon="$getFileIcon($attachment['type'])" class="fs-1 text-muted" />
                @endif
            </div>

            <div class="uploader-item-info">
                <div class="uploader-item-name">{{ $attachment['name'] }}</div>
                <div class="uploader-item-size">{{ $formatFileSize($attachment['size']) }}</div>
            </div>
        </a>
    @endforeach
</div>
