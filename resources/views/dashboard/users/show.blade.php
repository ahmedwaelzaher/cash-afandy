<x-layouts::dashboard inline>
    <div class="card">
        <div class="card-header">
            <p class="card-title">{{ __('Profile') }}</p>
        </div>

        <div class="card-body">
            <div class="datagrid">
                <div class="datagrid-item">
                    <div class="datagrid-title">{{ __('Name') }}</div>
                    <div class="datagrid-content">{{ $user->full_name }}</div>
                </div>

                <div class="datagrid-item">
                    <div class="datagrid-title">{{ __('Email') }}</div>
                    <div class="datagrid-content"><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></div>
                </div>

                <div class="datagrid-item">
                    <div class="datagrid-title">{{ __('Registered At') }}</div>
                    <div class="datagrid-content">{{ $user->created_at->translatedFormat('d F Y') }}</div>
                </div>

                <div class="datagrid-item">
                    <div class="datagrid-title">{{ __('Registered Since') }}</div>
                    <div class="datagrid-content">{{ $user->created_at->diffForHumans() }}</div>
                </div>

                <div class="datagrid-item">
                    <div class="datagrid-title">{{ __('Updated At') }}</div>
                    <div class="datagrid-content">{{ $user->updated_at->translatedFormat('d F Y') }}</div>
                </div>

                <div class="datagrid-item">
                    <div class="datagrid-title">{{ __('Verified') }}</div>
                    <div class="datagrid-content">
                        @if ($user->email_verified_at)
                            <span class="badge bg-success-lt">{{ __('Yes') }}</span>
                        @else
                            <span class="badge bg-danger-lt">{{ __('No') }}</span>
                        @endif
                    </div>
                </div>

                <div class="datagrid-item">
                    <div class="datagrid-title">{{ __('Verified At') }}</div>
                    <div class="datagrid-content">
                        @if ($user->email_verified_at)
                            {{ $user->email_verified_at->format('Y-m-d h:i A') }}
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if (route_allowed('dashboard.referrals.index'))
        <div class="card mt-3" id="invitations">
            <div class="card-header"><h2 class="card-title">{{ __('Invitations') }}</h2></div>
            <div class="card-body">
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Invited by') }}</div>
                        <div class="datagrid-content">{{ $user->referral?->referrer?->full_name ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Registered At') }}</div>
                        <div class="datagrid-content">{{ $user->referral?->registered_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">{{ __('Registrations through invitations') }}</div>
                        <div class="datagrid-content">{{ number_format($user->accepted_referrals_count) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3"><livewire:datatables.referrals :user-id="$user->id" /></div>
    @endif
</x-layouts::dashboard>
