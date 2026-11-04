@php
    $accountUser = $user ?? null;
    $accountCountries = \App\Models\Country::forAccounts()
        ->when($accountUser?->wallet, fn ($query) => $query->where('currency', $accountUser->wallet->currency))
        ->when($accountUser?->country_id && $accountUser?->wallet, fn ($query) => $query->whereKey($accountUser->country_id))
        ->orderBy('name')->get();
    $suggestedCountryId = $accountUser?->country_id ?? website_ip_country()?->id;
    $countryOptions = ['' => __('Choose your account country')] + $accountCountries->mapWithKeys(fn ($country) => [
        $country->id => app()->isLocale('ar') ? $country->native : $country->name,
    ])->all();
@endphp

<div class="mb-3">
    <x-select id="account-country" name="country_id" :title="__('Account country')" :options="$countryOptions"
        :value="old('country_id', $suggestedCountryId)" :tom="false" validation="required" aria-describedby="account-country-warning" />
    @foreach ($accountCountries as $accountCountry)
        <div class="mt-2" visible-when="$account-country == '{{ $accountCountry->id }}'" @style(['display: none' => (string) old('country_id', $suggestedCountryId) !== (string) $accountCountry->id])>
            <x-hint>{{ __('Your wallet currency: :currency', ['currency' => $accountCountry->currency]) }}</x-hint>
        </div>
    @endforeach
    <x-hint id="account-country-warning" class="d-block mt-2">{{ __('Choose the country where you live and receive your earnings. Your account country and wallet currency cannot be changed after confirmation.') }}</x-hint>
</div>
