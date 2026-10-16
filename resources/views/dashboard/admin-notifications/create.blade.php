<x-layouts::dashboard>
    @if ($admins->count())
        <x-form class="card" :action="route('dashboard.admin-notifications.store')" method="POST">
            <div class="card-header">
                <div class="card-title">{{ __('Send Notification') }}</div>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <x-select-remote name="admins[]" :title="__('Administrators')" :query="$admins" template="admin" :value="old('admins', [])" multiple validation="required" />
                </div>

                <div class="mb-3">
                    <x-input name="title" :title="__('Title')" :value="old('title')" validation="required" />
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <x-select name="level" :title="__('Level')" :options="$levels" :value="old('level', 'info')" :tom="false" validation="required" />
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <x-input name="url" :title="__('URL')" :value="old('url')" />
                    </div>
                </div>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">{{ __('Send') }}</button>
            </div>
        </x-form>
    @else
        <x-empty icon="fas fa-bell-slash" :title="__('No admins found')" :subtitle="__('You need to create an admin first to be able to send notifications.')" />
    @endif
</x-layouts::dashboard>
