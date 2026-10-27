<x-layouts::dashboard>
    <x-page-header :create="route('dashboard.offers.create')" class="mb-3" />
    <livewire:datatables.offers />
</x-layouts::dashboard>
