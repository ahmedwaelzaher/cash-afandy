<div class="card card-md" data-browser-notifications>
    <div class="card-body p-lg-4">
        <h2 class="h2 mb-2">{{ __('Browser notifications') }}</h2>
        <p class="text-secondary mb-4">{{ __('Manage notifications for this browser only.') }}</p>
        <p data-push-status role="status" aria-live="polite">{{ __('Browser notifications are disabled.') }}</p>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" data-push-enable hidden>{{ __('Enable browser notifications') }}</button>
            <button type="button" class="btn btn-outline-secondary" data-push-disable hidden>{{ __('Disable browser notifications') }}</button>
        </div>
    </div>
</div>
