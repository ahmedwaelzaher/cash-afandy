@php
    $sorts = [
        'latest' => __('Latest'),
        'highest' => __('Highest discount'),
        'expiring' => __('Expiring soon'),
    ];
@endphp

<form action="{{ $action }}" method="GET" class="card card-body mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-lg-4">
            <div class="input-icon">
                <input type="search" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="{{ __('Search') }}...">
                <span class="input-icon-addon">
                    <i class="fa fa-search"></i>
                </span>
            </div>
        </div>

        <div class="col-6 col-lg-2">
            <select name="category" class="form-select" aria-label="{{ __('Category') }}">
                <option value="">{{ __('All categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>
                        {{ $category->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-lg-2">
            <select name="store" class="form-select" aria-label="{{ __('Store') }}">
                <option value="">{{ __('All stores') }}</option>
                @foreach ($stores as $store)
                    <option value="{{ $store->slug }}" @selected(request('store') === $store->slug)>
                        {{ $store->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-lg-2">
            <select name="sort" class="form-select" aria-label="{{ __('Sort by') }}">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}" @selected(request('sort', 'latest') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-brand flex-fill">{{ __('Filter') }}</button>

            @if (request()->hasAny(['search', 'category', 'store', 'sort']))
                <a href="{{ $action }}" class="btn btn-icon" aria-label="{{ __('Reset') }}" title="{{ __('Reset') }}">
                    <i class="fa fa-rotate-left"></i>
                </a>
            @endif
        </div>
    </div>
</form>
