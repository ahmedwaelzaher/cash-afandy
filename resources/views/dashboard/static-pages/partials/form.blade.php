<div class="mb-3">
    <x-translatable component="input" name="title" :value="$entry ? $entry->getTranslations('title') : old('title')" :title="__('Title')" validation="required" />
</div>

<div class="mb-3">
    <x-translatable component="rich-editor" name="content" :value="$entry ? $entry->getTranslations('content') : old('content')" :title="__('Content')" />
</div>

<div class="mb-3">
    <x-toggle name="active" :title="__('Active')" :value="old('active', $entry?->active ?? true)" :on="__('Yes')" :off="__('No')" />
</div>
