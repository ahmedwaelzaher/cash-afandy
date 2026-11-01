<div class="card card-md" data-browser-notifications>
    <div class="card-body p-lg-4 d-flex align-items-center justify-content-between gap-3">
        <p class="mb-0" id="browser-notifications-status" data-push-status role="status" aria-live="polite">{{ __('Browser notifications are disabled.') }}</p>
        <label class="form-check form-switch mb-0 flex-shrink-0">
            <input type="checkbox" class="form-check-input" role="switch" data-push-toggle aria-label="{{ __('Notifications') }}" aria-describedby="browser-notifications-status" disabled>
        </label>
    </div>
</div>
