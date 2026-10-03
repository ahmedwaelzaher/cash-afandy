<x-layouts::website :title="__('Add Category')">
    <section class="py-5">
        <div class="container container-tight">
            <x-form class="card" :action="route('website.finance.categories.store')" method="POST">
                <div class="card-header">
                    <h1 class="card-title">{{ __('Add Category') }}</h1>
                </div>

                <div class="card-body">
                    @include('website.finance.categories.partials.form', ['entry' => null])
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('website.finance.categories.index') }}" class="btn">{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                </div>
            </x-form>
        </div>
    </section>
</x-layouts::website>
