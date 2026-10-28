<x-layouts::dashboard>
    <x-form-card resource="offers" :entry="$offer" :data="['stores' => $stores, 'countries' => $countries, 'representatives' => $representatives]" />
</x-layouts::dashboard>
