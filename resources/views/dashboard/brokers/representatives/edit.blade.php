<x-layouts::dashboard>
    <x-form-card resource="brokers.representatives" :entry="$representative" :route-params="[$broker]" :data="['stores' => $stores, 'countries' => $countries]" />
</x-layouts::dashboard>
