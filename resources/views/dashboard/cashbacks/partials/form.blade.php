<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input type="url" name="url" :title="__('URL')" :value="old('url', $entry?->url)" validation="required|url" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-input type="number" step="0.01" name="percentage" :title="__('Percentage')" :value="old('percentage', $entry?->percentage)" validation="required|numeric|min:0|max:100" />
    </div>

    <div class="col-12 col-md-3 mb-3 d-flex align-items-end">
        <x-toggle name="active" :title="__('Active')" :value="old('active', $entry?->active ?? true)" :on="__('Yes')" :off="__('No')" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <x-select-remote name="client_id" :title="__('Client')" :query="$clients" text="title" :value="old('client_id', $entry?->client_id)" validation="required" />
    </div>

    <div class="col-12 col-md-4 mb-3">
        <x-select-remote name="representative_id" :title="__('Representative')" :query="$representatives" text="full_name" :value="old('representative_id', $entry?->representative_id)" validation="required" />
    </div>

    <div class="col-12 col-md-4 mb-3">
        <x-select-remote name="country_id" :title="__('Country')" :query="$countries" text="name" :value="old('country_id', $entry?->country_id)" validation="required" />
    </div>
</div>

<div class="mb-3">
    <x-repeater name="details" :title="__('Cashback Details')" :value="old('details', $entry?->details ?? [])">
        <x-repeater-card>
            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <x-input name="category" :title="__('Category')" validation="required" />
                </div>

                <div class="col-12 col-md-6 mb-3">
                    <x-input type="number" step="0.01" name="value" :title="__('Value')" validation="required|numeric|min:0|max:100" />
                </div>
            </div>
        </x-repeater-card>
    </x-repeater>
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="terms" :value="$entry ? $entry->getTranslations('terms') : old('terms')" :title="__('Terms')" />
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="how_it_works" :value="$entry ? $entry->getTranslations('how_it_works') : old('how_it_works')" :title="__('How It Works')" />
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="tips" :value="$entry ? $entry->getTranslations('tips') : old('tips')" :title="__('Tips')" />
</div>

<div class="row">
    <div class="col-12 col-md-3 mb-3">
        <x-input type="number" name="verification_period" :title="__('Verification Period (days)')" :value="old('verification_period', $entry?->verification_period)" validation="nullable|integer|min:0" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-date-picker name="launch_date" :title="__('Launch Date')" :value="old('launch_date', $entry?->launch_date ?? now())" datetime />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-date-picker name="expiration_date" :title="__('Expiration Date')" :value="old('expiration_date', $entry?->expiration_date)" datetime />
    </div>
</div>
