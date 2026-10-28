@pushOnce('styles', 'auth-split-styles')
    <link rel="stylesheet" href="{{ hashed_asset('assets/css/login.css') }}" />
@endPushOnce

<div class="modal fade site-login-modal" id="site-login-modal" tabindex="-1" aria-labelledby="site-login-title" aria-hidden="true"
    data-login-url="{{ route('website.login') }}" data-pending-action-url="{{ route('website.pending-action.store') }}"
    data-authenticated="{{ auth('users')->check() ? 'true' : 'false' }}">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4">
            <div class="modal-body p-4 pt-5">
                <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                <div class="alert alert-danger" data-login-error role="alert" hidden></div>
                @include('website.auth.partials.login-form', ['modal' => true])
            </div>
        </div>
    </div>
</div>
