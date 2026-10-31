<aside class="site-push-prompt card" data-push-prompt hidden aria-labelledby="site-push-prompt-title">
    <div class="card-body p-2">
        <div class="d-flex align-items-center gap-2">
            <span class="site-push-prompt-icon rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                <x-icon icon="ti ti-bell" />
            </span>
            <h2 class="h4 flex-fill mb-0" id="site-push-prompt-title">{{ __('Be the first to know') }}</h2>
            <button type="button" class="btn-close flex-shrink-0" data-push-dismiss aria-label="{{ __('Close') }}"></button>
        </div>
        <p class="mb-0 mt-1">{{ __('Get the latest offers and price alerts.') }}</p>
        <p class="text-danger small mt-2 mb-0" data-push-prompt-status role="status" aria-live="polite" hidden></p>
        <div class="d-flex justify-content-end flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-sm btn-primary" data-push-enable>
                {{ __('Enable notifications') }}
            </button>
            <button type="button" class="nav-link px-3 py-2" data-push-dismiss>{{ __('Later') }}</button>
        </div>
    </div>
</aside>
