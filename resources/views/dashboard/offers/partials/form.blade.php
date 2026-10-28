<div class="mb-3">
    <x-translatable component="input" name="title" :value="$entry ? $entry->getTranslations('title') : old('title')" :title="__('Title')" validation="required" />
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input name="code" :title="__('Code')" :value="old('code', $entry?->code)" validation="required|max:50" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-select-remote name="store_id" :title="__('Store')" :query="$stores" text="title" :value="old('store_id', $entry?->store_id)" validation="required" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-select-remote name="representative_id" :title="__('Representative')" :query="$representatives" text="full_name" :value="old('representative_id', $entry?->representative_id)" validation="required" />
    </div>
</div>

<div class="mb-3">
    <x-select-remote name="countries[]" :title="__('Countries')" :query="$countries" text="name" :value="old('countries', $entry?->countries->pluck('id')->toArray() ?? [])" multiple validation="required" />
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="description" :value="$entry ? $entry->getTranslations('description') : old('description')" :title="__('Description')" />
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="tips" :value="$entry ? $entry->getTranslations('tips') : old('tips')" :title="__('Tips')" />
</div>

<div class="row">
    <div class="col-12 col-md-3 mb-3">
        <x-input type="number" step="0.01" name="discount" :title="__('Discount')" :value="old('discount', $entry?->discount)" validation="required|numeric|min:0|max:100" />
    </div>

    <div class="col-12 col-md-3 mb-3 d-flex align-items-end">
        <x-toggle name="fixed_discount" :title="__('Fixed Amount')" :value="old('fixed_discount', $entry?->fixed_discount ?? false)" :on="__('Yes')" :off="__('No')" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-input type="number" step="0.01" name="minimum_amount" :title="__('Minimum Amount')" :value="old('minimum_amount', $entry?->minimum_amount)" validation="nullable|numeric|min:0" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-input type="number" step="0.01" name="maximum_amount" :title="__('Maximum Amount')" :value="old('maximum_amount', $entry?->maximum_amount)" validation="nullable|numeric|min:0" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-3 mb-3">
        <x-input type="number" name="minimum_usages" :title="__('Minimum Usages')" :value="old('minimum_usages', $entry?->minimum_usages)" validation="nullable|integer|min:0" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-input type="number" name="maximum_usages" :title="__('Maximum Usages')" :value="old('maximum_usages', $entry?->maximum_usages)" validation="nullable|integer|min:0" />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-date-picker name="launch_date" :title="__('Launch Date')" :value="old('launch_date', $entry?->launch_date ?? now())" datetime />
    </div>

    <div class="col-12 col-md-3 mb-3">
        <x-date-picker name="expiration_date" :title="__('Expiration Date')" :value="old('expiration_date', $entry?->expiration_date)" datetime />
    </div>
</div>

<div class="mb-3">
    <x-toggle name="active" :title="__('Active')" :value="old('active', $entry?->active ?? true)" :on="__('Yes')" :off="__('No')" />
</div>
