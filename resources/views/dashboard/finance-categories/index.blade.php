<x-layouts::dashboard>
    <x-page-header :create="route('dashboard.finance-categories.create')" class="mb-3" />
    <livewire:datatables.finance-categories />
</x-layouts::dashboard>
