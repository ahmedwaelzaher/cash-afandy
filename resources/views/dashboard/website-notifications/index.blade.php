<x-layouts::dashboard>
    <x-page-header :create="route('dashboard.website-notifications.create')" class="mb-3" />
    <livewire:datatables.scheduled-website-notifications />
</x-layouts::dashboard>
