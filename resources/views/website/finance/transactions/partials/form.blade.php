{{--
    Transaction fields, shared by the quick add modal and the edit page.

    @param \App\Models\Transaction|null $entry
    @param \Illuminate\Support\Collection $categories  Available categories grouped by type.
--}}
<div class="mb-3">
    <x-input type="number" name="amount" :title="__('Amount')" :value="old('amount', $entry?->amount)" step="0.01" min="0.01"
        :append="__('EGP')" validation="required" />
</div>

<div class="mb-3">
    <x-select name="finance_category_id" :title="__('Category')" validation="required">
        <option value="">{{ __('Select a category') }}</option>

        @foreach (\App\Enums\TransactionType::cases() as $type)
            <optgroup label="{{ $type->label() }}">
                @foreach ($categories->get($type->value, collect()) as $category)
                    <option value="{{ $category->id }}" @selected(old('finance_category_id', $entry?->finance_category_id) == $category->id)>
                        {{ $category->title }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </x-select>
</div>

<div class="mb-3">
    <x-input type="date" name="occurred_on" :title="__('Date')"
        :value="old('occurred_on', $entry?->occurred_on?->format('Y-m-d') ?? now()->toDateString())" validation="required" />
</div>

<div class="mb-3">
    <x-input name="note" :title="__('Note')" :value="old('note', $entry?->note)" :placeholder="__('Optional')" />
</div>
