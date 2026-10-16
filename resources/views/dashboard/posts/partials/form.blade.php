<div class="row">
    <div class="col-12 col-md-8 mb-3">
        <x-input name="title" :title="__('Title')" :value="old('title', $entry?->title)" validation="required" />
    </div>

    <div class="col-12 col-md-4 mb-3">
        <x-select name="locale" :title="__('Locale')" :options="$locales" :value="old('locale', $entry?->locale)" :tom="false" validation="required" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input name="slug" :title="__('Slug')" :value="old('slug', $entry?->slug)" validation="required|alpha_dash" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-select-remote name="category_id" :title="__('Category')" :query="$categories" text="title" :value="old('category_id', $entry?->category_id)" removable />
    </div>
</div>

<div class="mb-3">
    <x-input name="excerpt" :title="__('Excerpt')" :value="old('excerpt', $entry?->excerpt)" validation="nullable|max:500" />
</div>

<div class="mb-3">
    <x-rich-editor name="content" :title="__('Content')" :value="old('content', $entry?->content)" validation="required" />
</div>

<div class="mb-3">
    <x-uploader name="cover_image" :title="__('Cover Image')" :value="old('cover_image', $entry?->cover_image)" directory="posts" accept="image/*" />
</div>

<div class="mb-3">
    <x-select name="media_type" :title="__('Media Type')" :options="['' => __('None'), 'image' => __('Image'), 'gallery' => __('Gallery'), 'video' => __('Video')]" :value="old('media_type', $entry?->media_type)" :tom="false" />
</div>

<div class="mb-3" visible-when="$media_type == 'image'">
    <x-uploader name="image" :title="__('Image')" directory="posts" accept="image/*" />
</div>

<div class="mb-3" visible-when="$media_type == 'gallery'">
    <x-uploader name="gallery[]" :title="__('Gallery')" directory="posts" accept="image/*" />
</div>

<div class="row" visible-when="$media_type == 'video'">
    <div class="col-12 col-md-6 mb-3">
        <x-input type="url" name="video" :title="__('Video URL')" validation="nullable|url" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-uploader name="thumbnail" :title="__('Thumbnail')" :value="old('thumbnail', $entry?->thumbnail)" directory="posts" accept="image/*" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-select name="tags[]" :title="__('Tags')" :options="$tags" :value="old('tags', $entry?->tags)" tags multiple />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-date-picker name="published_at" :title="__('Published At')" :value="old('published_at', $entry?->published_at)" datetime />
    </div>
</div>
