<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input name="name" :title="__('Name')" :value="old('name', $entry?->name)" :disabled="$entry !== null" validation="required" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-select-remote name="role" :title="__('Role')" :query="\Spatie\Permission\Models\Role::class" key="name" :value="old('role', $entry?->roles()->first()?->name)" create="dashboard.roles.create" removable />
    </div>
</div>

<div class="mb-3">
    <x-input type="email" name="email" :title="__('Email address')" :value="old('email', $entry?->email)" validation="required|email" />
</div>

<div class="mb-3">
    <x-toggle name="active" :title="__('Active')" :value="old('active', $entry?->active ?? true)" :on="__('Yes')" :off="__('No')" />
</div>
