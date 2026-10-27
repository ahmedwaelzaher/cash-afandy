<x-layouts::dashboard>
    <x-form-card resource="offers" :data="['clients' => $clients, 'countries' => $countries, 'representatives' => $representatives]" />
</x-layouts::dashboard>
