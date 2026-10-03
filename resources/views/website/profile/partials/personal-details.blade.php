{{--
    Personal details fields shared by registration, profile completion and profile update.

    @param array $details     Current values: phone, birthdate (Y-m-d), birth_day, birth_month, birth_year, gender, country_id, state_id.
    @param bool  $splitBirthdate  Show the birthdate as day / month / year selects, so a partial date can be prefilled.
--}}
@php
    $details ??= [];
    $splitBirthdate ??= false;
    $value = fn (string $key) => old($key, $details[$key] ?? null);
@endphp

<div class="mb-3">
    <x-input type="tel" name="phone" :title="__('Phone Number')" :value="$value('phone')" :placeholder="__('Phone Number')" />
</div>

@if ($splitBirthdate)
    <x-label :title="__('Birthdate')" for="birth-day" required />

    <div class="row g-2 mb-3">
        <div class="col-4">
            <x-select id="birth-day" name="birth_day" :tom="false" :options="['' => __('Day')] + array_combine(range(1, 31), range(1, 31))"
                :value="$value('birth_day')" validation="required" />
        </div>

        <div class="col-4">
            <x-select id="birth-month" name="birth_month" :tom="false"
                :options="['' => __('Month')] + collect(range(1, 12))->mapWithKeys(fn ($month) => [$month => now()->startOfYear()->month($month)->translatedFormat('F')])->all()"
                :value="$value('birth_month')" validation="required" />
        </div>

        <div class="col-4">
            <x-select id="birth-year" name="birth_year" :tom="false" :options="['' => __('Year')] + array_combine(range(now()->year, now()->year - 100), range(now()->year, now()->year - 100))"
                :value="$value('birth_year')" validation="required" />
        </div>
    </div>
@else
    <div class="mb-3">
        <x-input type="date" name="birthdate" :title="__('Birthdate')" :value="$value('birthdate')" validation="required" />
    </div>
@endif

<div class="mb-3">
    <x-radios name="gender" :title="__('Gender')" :options="\App\Enums\Gender::values()" :value="$value('gender')" validation="required" inline />
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-countries id="country-id" name="country_id" :title="__('Country')" :value="$value('country_id')" validation="required" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-select id="state-id" name="state_id" :title="__('State')" :value="$value('state_id')" validation="required"
            :query="\App\Models\State::query()->where('country_id', '{country}')"
            :text="app()->getLocale() === 'ar' ? 'native' : 'name'" bind-country.selector="#country-id" />
    </div>
</div>
