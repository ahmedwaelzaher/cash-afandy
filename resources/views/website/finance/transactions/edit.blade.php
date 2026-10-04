<x-layouts::website :title="__('Edit Transaction')">
    <section class="py-5">
        <div class="container container-tight">
            <x-form class="card" :action="route('website.finance.transactions.update', $transaction)" method="PUT">
                <div class="card-header">
                    <h1 class="card-title">{{ __('Edit Transaction') }}</h1>
                </div>

                <div class="card-body">
                    @include('website.finance.transactions.partials.form', ['entry' => $transaction])
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('website.finance.transactions.index') }}" class="btn">{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </x-form>
        </div>
    </section>
</x-layouts::website>
