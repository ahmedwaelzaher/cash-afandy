<x-layouts::dashboard>
    @if ($admins->count())
        <x-form class="card" :action="route('dashboard.impersonate-admins.store')" method="POST">
            <div class="card-header">
                <div class="card-title">{{ __('Impersonate Admin') }}</div>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <x-select-remote name="admin_id" :title="__('Admin')" :query="$admins" template="admin" validation="required" />
                </div>
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">{{ __('Impersonate') }}</button>
            </div>
        </x-form>
    @else
        <x-empty icon="fas fa-user-slash" :title="__('No admins found')" :subtitle="__('You need to create an admin first to be able to impersonate him.')" />
    @endif
</x-layouts::dashboard>
