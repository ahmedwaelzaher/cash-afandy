<x-layouts::dashboard>
    <x-form-card resource="brokers.representatives" :route-params="[$broker]" :data="['stores' => $stores, 'countries' => $countries]" />
</x-layouts::dashboard>
