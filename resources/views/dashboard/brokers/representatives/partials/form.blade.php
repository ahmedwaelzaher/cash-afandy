<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input name="first_name" :title="__('First name')" :value="old('first_name', $entry?->first_name)" validation="required" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-input name="last_name" :title="__('Last name')" :value="old('last_name', $entry?->last_name)" validation="required" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <x-input type="email" name="email" :title="__('Email')" :value="old('email', $entry?->email)" validation="nullable|email" />
    </div>

    <div class="col-12 col-md-6 mb-3">
        <x-phone name="phone" :title="__('Phone')" :value="old('phone', $entry?->phone)" />
    </div>
</div>

<div class="mb-3">
    <x-repeater name="stores" :title="__('Store Assignments')" :value="old('stores', $entry?->stores ?? [])">
        <x-repeater-card>
            <div class="row">
                <div class="col-12 col-md-6 mb-3">
                    <x-select-remote name="store_id" :title="__('Store')" :query="$stores" text="title" validation="required" />
                </div>

                <div class="col-12 col-md-6 mb-3">
                    <x-select-remote name="country_id" :title="__('Country')" :query="$countries" text="name" validation="required" />
                </div>
            </div>
        </x-repeater-card>
    </x-repeater>
</div>
