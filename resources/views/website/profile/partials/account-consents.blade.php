@php
    $termsLink = '<a class="text-decoration-underline" href="' . e(static_page_url('terms-and-conditions')) . '" target="_blank" rel="noopener noreferrer">' . e(__('Terms and Conditions')) . '</a>';
    $privacyLink = '<a class="text-decoration-underline" href="' . e(static_page_url('privacy-policy')) . '" target="_blank" rel="noopener noreferrer">' . e(__('Privacy Policy')) . '</a>';
    $termsLabel = new \Illuminate\Support\HtmlString(strtr(e(__('I agree to :terms and :privacy')), [':terms' => $termsLink, ':privacy' => $privacyLink]));
@endphp

<div class="mb-3">
    <x-checkboxes id="account-country-warning" name="account_country_confirmation"
        :options="[1 => __('I confirm that my selected account country is correct. I understand that it determines my wallet currency and that neither can be changed after confirmation.')]"
        :value="old('account_country_confirmation')" validation="required|accepted" />
    <x-checkboxes id="account-terms" name="terms_accepted" :options="[1 => $termsLabel]"
        :value="old('terms_accepted')" validation="required|accepted" />
</div>
