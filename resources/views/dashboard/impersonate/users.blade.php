<x-layouts::dashboard>
    @if ($users->count())
        <x-form class="card" :action="route('dashboard.impersonate-users.store')" method="POST">
            <div class="card-header">
                <div class="card-title">{{ __('Impersonate User') }}</div>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <x-select-remote name="user_id" :title="__('User')" :query="$users" text="full_name" search="full_name, email"
                        template="user" validation="required" />
                </div>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">{{ __('Impersonate') }}</button>
            </div>
        </x-form>
    @else
        <x-empty icon="fas fa-user-slash" :title="__('No users found')" :subtitle="__('You need to create a user first to be able to impersonate him.')" />
    @endif
</x-layouts::dashboard>
