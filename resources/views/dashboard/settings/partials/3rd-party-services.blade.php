<div class="mb-3">
    <x-input name="facebook_pixel_id" :title="__('Facebook Pixel ID')" value="{{ setting('facebook_pixel_id') }}" />
</div>

<div class="mb-3">
    <x-input name="google_analytics_property_id" :title="__('Google Analytics property ID')"
        value="{{ setting('google_analytics_property_id') }}" />
</div>

<div class="mb-3">
    <x-input name="cloudflare_turnstile_site_key" :title="__('Cloudflare Turnstile Site Key')"
        value="{{ setting('cloudflare_turnstile_site_key') }}" />
</div>

<div class="mb-3">
    <x-input name="cloudflare_turnstile_secret_key" :title="__('Cloudflare Turnstile Secret Key')"
        value="{{ setting('cloudflare_turnstile_secret_key') }}" />
</div>
