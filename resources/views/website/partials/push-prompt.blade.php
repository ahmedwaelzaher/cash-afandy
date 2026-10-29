<aside class="site-push-prompt card" data-push-prompt hidden aria-labelledby="site-push-prompt-title">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <span class="site-push-prompt-icon rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                <x-icon icon="ti ti-bell" />
                <x-icon icon="ti ti-sparkles" class="site-push-prompt-sparkle" />
            </span>
            <div class="flex-fill">
                <h2 class="h3 mb-2" id="site-push-prompt-title">{{ __('Be the first to know') }}</h2>
                <p class="mb-0">{{ __('Enable notifications to follow your cashback and price alerts.') }}</p>
            </div>
            <button type="button" class="btn-close flex-shrink-0" data-push-dismiss aria-label="{{ __('Close') }}"></button>
        </div>
        <p class="text-danger small mt-3 mb-0" data-push-prompt-status role="status" aria-live="polite" hidden></p>
        <div class="d-flex gap-2 mt-4">
            <button type="button" class="btn btn-primary flex-fill gap-2" data-push-enable>
                <x-icon icon="ti ti-bell" />
                {{ __('Enable browser notifications') }}
            </button>
            <button type="button" class="btn btn-outline-secondary flex-fill" data-push-dismiss>{{ __('Later') }}</button>
        </div>
        <p class="small text-center mb-0 mt-3 d-flex align-items-center justify-content-center gap-2">
            <x-icon icon="ti ti-settings" />
            {{ __('You can turn them off anytime from your browser settings.') }}
        </p>
    </div>
</aside>
