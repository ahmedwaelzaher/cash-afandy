<x-layouts::dashboard>
    <x-form-card resource="offers" :entry="$offer" :data="['clients' => $clients, 'countries' => $countries, 'representatives' => $representatives]" />
</x-layouts::dashboard>
