@foreach (['google' => __('Google'), 'facebook' => __('Facebook')] as $provider => $label)
    <div class="mb-3">
        <x-input name="{{ $provider }}_client_id" :title="__(':provider App ID', ['provider' => $label])"
            value="{{ setting($provider . '_client_id') }}"
            :hint="__('Callback URLs') . ': ' . collect(setting('website_locales'))->map(fn ($locale) => route('website.socialite.callback', ['locale' => $locale, 'provider' => $provider]))->implode(' , ')" />
    </div>

    <div class="mb-3">
        <x-input name="{{ $provider }}_client_secret" :title="__(':provider App Secret', ['provider' => $label])"
            value="{{ setting($provider . '_client_secret') }}" />
    </div>
@endforeach
