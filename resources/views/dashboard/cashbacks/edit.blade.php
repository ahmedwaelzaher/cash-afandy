<x-layouts::dashboard>
    <x-form-card resource="cashbacks" :entry="$cashback" :data="['stores' => $stores, 'countries' => $countries, 'representatives' => $representatives]" />
</x-layouts::dashboard>
