<div class="mb-3">
    <x-input name="title" :title="__('Title')" :value="old('title', $entry?->title)" validation="required" />
</div>

<div class="mb-3">
    <x-radios name="type" :title="__('Type')" :options="\App\Enums\TransactionType::values()"
        :value="old('type', $entry?->type?->value ?? $type ?? \App\Enums\TransactionType::Expense->value)" validation="required" inline />
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-icon-picker name="icon" :title="__('Icon')" :value="old('icon', $entry?->icon)" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-color-picker name="color" :title="__('Color')" :value="old('color', $entry?->color)" />
    </div>
</div>
