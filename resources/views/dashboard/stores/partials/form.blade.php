<div class="mb-3">
    <x-translatable component="input" name="title" :value="$entry ? $entry->getTranslations('title') : old('title')" :title="__('Title')" validation="required" />
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input name="slug" :title="__('Slug')" :value="old('slug', $entry?->slug)" validation="required|alpha_dash" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-input type="url" name="url" :title="__('URL')" :value="old('url', $entry?->url)" validation="required|url" />
    </div>
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="description" :value="$entry ? $entry->getTranslations('description') : old('description')" :title="__('Description')" />
</div>

<div class="mb-3">
    <x-uploader name="logo" :title="__('Logo')" :value="old('logo', $entry?->logo)" directory="stores" />
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-select-remote name="categories[]" :title="__('Categories')" :query="$categories" text="title" :value="old('categories', $entry?->categories->pluck('id')->toArray() ?? [])" multiple validation="required" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-select-remote name="countries[]" :title="__('Countries')" :query="$countries" text="name" :value="old('countries', $entry?->countries->pluck('id')->toArray() ?? [])" multiple validation="required" />
    </div>
</div>

<div class="mb-3">
    <x-toggle name="active" :title="__('Active')" :value="old('active', $entry?->active ?? true)" :on="__('Yes')" :off="__('No')" />
</div>
