@php
    $preferences = $user->preferences;
    $notificationTypes = \App\Enums\NotificationType::values();
@endphp

<x-form class="card card-md" :action="route('website.profile.preferences.update')" method="PUT" data-browser-notifications>
    <div class="card-body p-lg-4">
        <div class="row g-3">
            <div class="col-12">
                <input type="hidden" name="notifications_enabled" value="0" />
                <x-toggle id="account-notifications-enabled" name="notifications_enabled" :title="__('Enable notifications')" :value="$preferences?->notifications_enabled ?? true" role="switch" />
            </div>
            <div class="col-12" visible-when="$account-notifications-enabled">
                <fieldset>
                    <legend class="form-label">{{ __('Notification types') }}</legend>
                    <x-checkboxes name="notification_types[]" :options="$notificationTypes" :value="$preferences?->enabledNotificationTypes() ?? array_keys($notificationTypes)" />
                    @foreach (array_keys($notificationTypes) as $type)
                        <input type="hidden" name="notification_type_options[]" value="{{ $type }}" />
                    @endforeach
                </fieldset>
            </div>
            <div class="col-12" visible-when="$account-notifications-enabled">
                <button type="button" class="btn btn-ghost-secondary" data-push-enable hidden>{{ __('Enable browser notifications') }}</button>
            </div>
        </div>
        <div class="mt-4 pt-3 border-top">
            <button type="submit" class="btn btn-primary px-4">{{ __('Save') }}</button>
        </div>
    </div>
</x-form>
