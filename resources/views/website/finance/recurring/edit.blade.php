<x-layouts::website :title="__('Edit Recurring Item')">
    <section class="py-5">
        <div class="container container-tight">
            <x-form class="card" :action="route('website.finance.recurring.update', $recurring)" method="PUT">
                <div class="card-header">
                    <h1 class="card-title">{{ __('Edit Recurring Item') }}</h1>
                </div>

                <div class="card-body">
                    @include('website.finance.recurring.partials.form', ['entry' => $recurring])
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('website.finance.recurring.index') }}" class="btn">{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </x-form>
        </div>
    </section>
</x-layouts::website>
