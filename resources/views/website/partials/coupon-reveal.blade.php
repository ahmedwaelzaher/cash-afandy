@pushOnce('templates', 'site-coupon-reveal')
    <div class="modal fade site-coupon-reveal" id="site-coupon-reveal" tabindex="-1" aria-labelledby="site-coupon-reveal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header border-0 p-4 pb-3">
                    <div class="d-flex align-items-center gap-2 text-secondary fw-semibold">
                        <span class="site-offer-icon flex-shrink-0" data-reveal-logo hidden>
                            <img class="object-fit-contain" alt="" width="32" height="32" referrerpolicy="no-referrer">
                        </span>
                        <span data-reveal-store></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <h3 class="h2 mb-4" id="site-coupon-reveal-title"></h3>
                    <div class="site-coupon-code d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <bdi class="fw-bold fs-1 text-break" dir="ltr" data-revealed-code></bdi>
                        <button type="button" class="btn gap-2" data-copy-revealed disabled>
                            <x-icon icon="ti ti-copy" /> <span data-copy-label>{{ __('Copy code') }}</span>
                        </button>
                    </div>
                    <p class="text-secondary mb-4" data-copy-status aria-live="polite"
                        data-copy-pending="{{ __('Preparing your coupon...') }}"
                        data-copy-success="{{ __('Copied. Paste the code at checkout.') }}"
                        data-copy-error="{{ __('Unable to copy the coupon code. Please copy it manually.') }}"
                        data-reveal-error="{{ __('Unable to reveal the coupon code. Please try again.') }}"
                        data-copied-label="{{ __('Copied') }}" data-copy-label="{{ __('Copy code') }}"></p>
                    <div data-reveal-link hidden>
                        <a class="btn btn-primary w-100 gap-2" target="_blank" rel="noopener noreferrer">
                            {{ __('Go to store') }} <x-icon icon="ti ti-external-link" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endPushOnce
