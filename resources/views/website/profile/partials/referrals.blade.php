@php
    $referralProgress = $user->referralProgress();
    $paused = ! setting('referral_program_enabled') && ! $referralProgress['completed'];
    $target = $referralProgress['target'];
    $amount = setting('referral_reward_amount');
    $invited = $referralProgress['count'];
    $remaining = $referralProgress['remaining'];
    $progress = $referralProgress['percentage'];
@endphp

<div class="card card-md position-relative overflow-hidden">
    <div class="card-body p-lg-4" @if ($paused) inert aria-hidden="true" @endif>
        @if ($user->referral_code)
            @foreach ([__('Invitation code') => $user->referral_code, __('Invitation link') => $user->invitationUrl()] as $label => $value)
                <div class="mb-3">
                    <label class="form-label" for="invitation-{{ $loop->index }}">{{ $label }}</label>
                    <div class="input-group input-group-flat">
                        <input id="invitation-{{ $loop->index }}" type="text" class="form-control" value="{{ $value }}" dir="ltr" readonly />
                        <button type="button" class="btn btn-primary" data-copy-text="{{ $value }}"
                            data-copy-success="{{ __('Copied') }}" data-copy-error="{{ __('Unable to copy. Please copy the text manually.') }}" aria-label="{{ __('Copy') }}">
                            <x-icon icon="ti ti-copy" class="m-0" />
                        </button>
                    </div>
                </div>
            @endforeach
        @endif

        <section class="site-referral-progress bg-primary-lt rounded-4 p-3 p-sm-4 mt-4" aria-labelledby="referral-progress-title">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <h3 class="h3 mb-0 d-flex align-items-center gap-2" id="referral-progress-title">
                    <x-icon icon="ti ti-users" class="m-0 flex-shrink-0" />
                    {{ __('Your invitations bring you closer to the reward') }}
                </h3>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-white text-primary">{{ __('Reward: :amount', ['amount' => number_format((float) $amount, 2)]) }}</span>
                    <span class="badge bg-white text-primary">
                        {{ $remaining ? __(':count invitations remaining', ['count' => number_format($remaining)]) : __('Invitation goal reached') }}
                    </span>
                </div>
            </div>
            <p class="d-flex align-items-center gap-2 mb-4 text-primary">
                <strong class="display-6 fw-bold lh-1">{{ number_format($invited) }}</strong>
                <span class="fs-3">{{ __('of :target friends', ['target' => number_format($target)]) }}</span>
            </p>
            <div class="d-flex align-items-center pb-4 mb-3">
                <div class="progress progress-sm flex-fill overflow-visible" role="progressbar" aria-labelledby="referral-progress-title"
                    aria-valuemin="0" aria-valuemax="{{ $target }}" aria-valuenow="{{ min($invited, $target) }}"
                    aria-valuetext="{{ __(':count of :target friends', ['count' => number_format($invited), 'target' => number_format($target)]) }}">
                    <div @class(['progress-bar bg-primary', 'site-referral-marker' => $remaining > 0]) style="width: {{ $progress }}%"></div>
                </div>
                <span @class(['avatar avatar-lg rounded-circle site-referral-goal d-inline-flex align-items-center justify-content-center position-relative overflow-visible flex-shrink-0 text-primary', 'border-primary' => $remaining === 0]) aria-hidden="true">
                    <x-icon icon="ti ti-gift" class="m-0 fs-1 lh-1 w-auto h-auto" />
                    <span class="site-referral-goal-label fs-5 fw-semibold">{{ __('Reward') }}</span>
                </span>
            </div>
            <p class="text-secondary mb-0">{{ __('Every friend who registers through your invitation brings you one step closer.') }}</p>
        </section>
    </div>
    @if ($paused)
        <div class="position-absolute top-0 start-0 w-100 h-100 z-3 d-flex align-items-center justify-content-center bg-white bg-opacity-75 p-4" role="status">
            <div class="card card-body text-center flex-grow-0">
                <x-icon icon="ti ti-player-pause" class="fs-1 lh-1 mb-3" />
                <p class="h3 mb-0">{{ __('The referral program has been paused.') }}</p>
            </div>
        </div>
    @endif
</div>
