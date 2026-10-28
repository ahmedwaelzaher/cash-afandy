<x-layouts::dashboard>
    <x-page-header :create="route('dashboard.stores.create')" class="mb-3" />
    <livewire:datatables.stores />
</x-layouts::dashboard>
