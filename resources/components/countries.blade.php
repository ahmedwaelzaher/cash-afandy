@props(['key' => 'id'])

@pushOnce('plugins-styles', 'tabler-flags-styles')
    <link rel="stylesheet" href="{{ hashed_asset('/vendor/tabler/css/tabler-flags.min.css') }}" />
@endPushOnce

<x-select-remote :query="\App\Models\Country::class" :key="$key" template="country" same-template {{ $attributes }} />
