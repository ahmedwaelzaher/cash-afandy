<x-layouts::dashboard>
    <x-form class="card" :action="route('dashboard.website-notifications.store')" method="POST" id="website-notification-form">
        <div class="card-header">
            <h2 class="card-title">{{ __('Website') }} — {{ __('Send Notification') }}</h2>
        </div>

        <div class="card-body">
            <div class="mb-3">
                <x-input name="title" :title="__('Title')" validation="required|max:255" maxlength="255" />
            </div>

            <div class="mb-3">
                <x-label for="notification-body" :title="__('Description')" required />
                <textarea id="notification-body" name="body" class="form-control" rows="4" validation="required|max:500" maxlength="500">{{ old('body') }}</textarea>
            </div>

            <div class="mb-3">
                <x-input name="url" type="url" :title="__('URL')" maxlength="2048" />
            </div>

            @php
                $recipientModes = ['custom' => __('Select') . ' ' . __('Users')];
                if (route_allowed('dashboard.website-notifications.recipients')) {
                    $recipientModes['condition'] = __('Filter');
                }
            @endphp
            <div class="mb-3">
                <x-radios name="recipient_mode" :title="__('Type')" :options="$recipientModes" :value="old('recipient_mode', 'custom')" :inline="true" validation="required" />
            </div>

            <div class="mb-3" visible-when="$recipient_mode == 'custom'">
                <x-select-remote id="notification-users" :title="__('Users')" :query="$users" template="user" text="full_name" search="first_name,last_name,email" :value="old('users', [])" multiple validation="required" />
                <input type="hidden" name="users" id="notification-user-ids" value="[]" />
            </div>

            @if (route_allowed('dashboard.website-notifications.recipients'))
                <div class="mb-3" visible-when="$recipient_mode == 'condition'">
                    <x-query-builder id="notification-filters" name="filters" :title="__('Filter')" :model="\App\Models\User::class" validation="required" />
                </div>
            @endif

            <div class="mb-3">
                <x-date-picker name="send_at" :title="__('Send') . ' — ' . __('Date')" :hint="config('app.timezone')" datetime validation="required" />
            </div>
        </div>

        <div class="card-footer text-end">
            @if (route_allowed('dashboard.website-notifications.index'))
                <a href="{{ route('dashboard.website-notifications.index') }}" class="btn">{{ __('Cancel') }}</a>
            @endif
            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </x-form>

    @push('scripts')
        <script>
            $('#website-notification-form').on('submit', () => {
                $('#notification-user-ids').val(JSON.stringify($('#notification-users').data('tomselect')?.items || []));
            });
        </script>
    @endpush
</x-layouts::dashboard>
