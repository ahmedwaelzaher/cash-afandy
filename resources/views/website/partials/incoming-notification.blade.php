<div class="site-notification-popups toast-container" data-notification-popups></div>

<template id="site-incoming-notification">
    <div class="toast site-notification-popup" role="status" aria-live="polite" aria-atomic="true" data-delay="5000">
        <div class="p-3">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <span class="d-inline-flex align-items-center gap-2 small text-primary">
                    <x-icon :icon="\App\Enums\NotificationType::General->icon()" class="fs-1 m-0 lh-1 w-auto h-auto" data-notification-type-icon data-notification-icon />
                    <span data-notification-type></span>
                </span>
                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="d-flex align-items-start gap-3">
                <span class="avatar avatar-lg bg-primary-lt d-inline-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden" aria-hidden="true" data-notification-avatar>
                    <x-icon :icon="\App\Enums\NotificationType::General->icon()" class="fs-1 m-0 lh-1 w-auto h-auto" data-notification-fallback data-notification-icon />
                </span>
                <div class="flex-fill overflow-hidden">
                    <h3 class="h4 mb-0 text-break" data-notification-title></h3>
                    <p class="small mb-0 mt-2 text-break" data-notification-body></p>
                </div>
            </div>
            <div class="mt-4 text-end" data-notification-action hidden>
                <a class="btn btn-primary" data-notification-link>{{ __('Details') }}</a>
            </div>
        </div>
    </div>
</template>
