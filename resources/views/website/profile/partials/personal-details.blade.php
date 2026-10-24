{{--
    Personal details fields shared by registration and profile update.

    @param array $details     Current values: phone, birthdate (Y-m-d), gender.
    @param bool  $showPhone  Show the optional phone field (hidden during registration).
    @param bool  $showBirthdate  Show the optional birthdate field (hidden during registration).
    @param bool  $requireGender  Require a gender selection during registration.
--}}
@php
    $details ??= [];
    $showPhone ??= true;
    $showBirthdate ??= true;
    $requireGender ??= false;
    $value = fn (string $key) => old($key, $details[$key] ?? null);
@endphp

@if ($showPhone)
    <div class="mb-3">
        <x-phone name="phone" :title="__('Phone Number')" :value="$value('phone')" />
    </div>
@endif

@if ($showBirthdate)
    <div class="mb-3">
        <x-input type="date" name="birthdate" :title="__('Birthdate')" :value="$value('birthdate')" />
    </div>
@endif

<div class="mb-3">
    <x-radios name="gender" :title="__('Gender')" :options="\App\Enums\Gender::values()" :value="$value('gender') ?? \App\Enums\Gender::Male->value" :validation="$requireGender ? 'required' : null" inline />
</div>
