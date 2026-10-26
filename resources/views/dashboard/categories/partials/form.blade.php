<div class="mb-3">
    <x-translatable component="input" name="title" :value="$entry ? $entry->getTranslations('title') : old('title')" :title="__('Title')" validation="required" />
</div>

<div class="mb-3">
    <x-input name="slug" :title="__('Slug')" :value="old('slug', $entry?->slug)" validation="required|alpha_dash" />
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="description" :value="$entry ? $entry->getTranslations('description') : old('description')" :title="__('Description')" />
</div>

<div class="mb-3">
    <x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon ?? 'ti ti-category')" validation="required" />
</div>
