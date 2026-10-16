<div class="row mb-3">
    <div class="col-auto" data-bs-theme="light">
        <x-avatar class="p-2" :image="setting('app_logo_light')" size="xl" logo-light
            style="background-size: contain; background-color: var(--tblr-bg-surface); background-origin: content-box;" />
    </div>

    <div class="col">
        <x-input type="file" name="app_logo_light" :title="__('Light theme logo')" accept="image/*" :hint="__('Recommended size: 150×24 px. max 1 MB.')"
            onchange="applyAvatarPreview(this, '[logo-light]')" />
    </div>
</div>

<div class="row mb-3">
    <div class="col-auto" data-bs-theme="dark">
        <x-avatar class="p-2" :image="setting('app_logo_dark')" size="xl" logo-dark
            style="background-size: contain; background-color: var(--tblr-bg-surface); background-origin: content-box;" />
    </div>

    <div class="col">
        <x-input type="file" name="app_logo_dark" :title="__('Dark theme logo')" accept="image/*" :hint="__('Recommended size: 150×24 px. max 1 MB.')"
            onchange="applyAvatarPreview(this, '[logo-dark]')" />
    </div>
</div>

<div class="mb-3">
    <x-translatable component="input" type="text" name="app_name" :title="__('App name')" :value="setting('app_name')"
        validation="required" />
</div>

<div class="mb-3">
    <x-translatable component="textarea" name="footer_about_description" :title="__('Footer about description')"
        :value="setting('footer_about_description')" />
</div>

<div class="mb-3">
    <x-translatable component="textarea" name="footer_subscribe_description" :title="__('Footer subscribe description')"
        :value="setting('footer_subscribe_description')" />
</div>

@if (config('redot.features.website.enabled'))
    <div class="mb-3">
        <x-checkboxes :title="__('Website Languages')" :options="config('app.locales')" :inline="true" name="website_locales[]" :value="setting('website_locales', [])"
            validation="required|min:1" />
    </div>

    <div class="mb-3">
        <x-toggle name="show_website_countries_dropdown" id="show-website-countries-dropdown" :title="__('Show Website Countries Dropdown')"
            :value="setting('show_website_countries_dropdown')"
            :hint="__('When disabled, the country dropdown is hidden and content is shown for all countries by default.')" />
    </div>

    <div class="mb-3" visible-when="$show-website-countries-dropdown">
        <x-select-remote name="website_countries[]" :title="__('Website Countries')" :query="\App\Models\Country::class" key="code"
            text="name" :value="setting('website_countries', [])" multiple validation="required|min:1" />
    </div>

    <div class="mb-3">
        <x-select-remote name="default_website_country" :title="__('Default Website Country')" :query="\App\Models\Country::class" key="code"
            text="name" :value="setting('default_website_country')" validation="required" />
    </div>
@endif

<div class="mb-3">
    <x-checkboxes :title="__('Dashboard Languages')" :options="config('app.locales')" :inline="true" name="dashboard_locales[]" :value="setting('dashboard_locales', [])"
        validation="required|min:1" />
</div>

<div class="mb-3">
    <x-toggle name="service_worker_enabled" :title="__('Service Worker')" :value="setting('service_worker_enabled')" />
</div>
