<div class="mb-3">
    <x-input name="title" :title="__('Title')" :value="old('title', $entry?->title)" validation="required" />
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input type="email" name="email" :title="__('Email')" :value="old('email', $entry?->email)" validation="nullable|email" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-phone name="phone" :title="__('Phone')" :value="old('phone', $entry?->phone)" />
    </div>
</div>

<div class="mb-3">
    <x-input type="url" name="url" :title="__('URL')" :value="old('url', $entry?->url)" validation="nullable|url" />
</div>
