<x-layouts::dashboard>
    <x-form-card resource="stores" :entry="$store" :data="['categories' => $categories, 'countries' => $countries]" />
</x-layouts::dashboard>
