{{--
    Recurring income or expense fields, shared by the create and edit pages.

    @param \App\Models\RecurringTransaction|null $entry
    @param \Illuminate\Support\Collection $categories  Available categories grouped by type.
--}}
<div class="mb-3">
    <x-input name="title" :title="__('Title')" :value="old('title', $entry?->title)" :placeholder="__('Salary, rent, internet...')" validation="required" />
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
    <x-radios name="nature" :title="__('Amount')" :options="\App\Enums\RecurrenceNature::values()"
        :value="old('nature', $entry?->nature?->value ?? \App\Enums\RecurrenceNature::Fixed->value)" validation="required" inline
        :hint="__('Fixed amounts are added automatically. Variable ones are added with the expected amount and wait for you to confirm the actual one.')" />
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input type="number" name="amount" :title="__('Expected amount')" :value="old('amount', $entry?->amount)" step="0.01" min="0.01"
            :append="__('EGP')" validation="required" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-select name="frequency" :title="__('Repeats')" :tom="false" :options="\App\Enums\RecurrenceFrequency::values()"
            :value="old('frequency', $entry?->frequency?->value ?? \App\Enums\RecurrenceFrequency::Monthly->value)" validation="required" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input type="date" name="starts_on" :title="__('Starts on')"
            :value="old('starts_on', $entry?->starts_on?->format('Y-m-d') ?? now()->toDateString())" validation="required"
            :hint="__('Also sets the day it repeats on. Past dates are not added automatically.')" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-input type="date" name="ends_on" :title="__('Ends on')" :value="old('ends_on', $entry?->ends_on?->format('Y-m-d'))"
            :hint="__('Leave empty if it keeps going.')" />
    </div>
</div>

<div class="mb-3">
    <x-toggle name="is_active" :title="__('Status')" :value="old('is_active', $entry?->is_active ?? true)" :on="__('Active')" :off="__('Paused')" />
</div>
