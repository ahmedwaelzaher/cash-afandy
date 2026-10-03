<x-layouts::website :title="__('Edit Category')">
    <section class="py-5">
        <div class="container container-tight">
            <x-form class="card" :action="route('website.finance.categories.update', $category)" method="PUT">
                <div class="card-header">
                    <h1 class="card-title">{{ __('Edit Category') }}</h1>
                </div>

                <div class="card-body">
                    @include('website.finance.categories.partials.form', ['entry' => $category])
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('website.finance.categories.index') }}" class="btn">{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </x-form>
        </div>
    </section>
</x-layouts::website>
