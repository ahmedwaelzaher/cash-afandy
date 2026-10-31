<x-layouts::website :title="__('Notifications')">
    <section class="site-static-page py-4" aria-labelledby="notifications-title">
        <div class="container site-container">
            <header class="mb-4">
                <h1 class="display-6 fw-bold mb-2" id="notifications-title">{{ __('Notifications') }}</h1>
                <p class="text-secondary mb-0">{{ __('Get the latest offers and price alerts.') }}</p>
            </header>
            <livewire:notifications :website="true" :full-page="true" />
        </div>
    </section>
</x-layouts::website>
