<div class="card card-md mt-3" data-browser-notifications hidden>
    <div class="card-body">
        <h2 class="h3">{{ __('Browser notifications') }}</h2>
        <p class="text-secondary">{{ __('Manage notifications for this browser only.') }}</p>
        <p data-push-status role="status" aria-live="polite"></p>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" data-push-enable>{{ __('Enable browser notifications') }}</button>
            <button type="button" class="btn btn-outline-secondary" data-push-disable hidden>{{ __('Disable browser notifications') }}</button>
        </div>
    </div>
</div>
