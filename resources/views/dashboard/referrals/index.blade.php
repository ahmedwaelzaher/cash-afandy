<x-layouts::dashboard>
    <x-page-header class="mb-3" />
    <div class="row g-3 mb-3">
        @foreach ([__('Registrations through invitations') => $total, __('Invited users with verified email now') => $verified] as $label => $value)
            <div class="col-12 col-md-6">
                <div class="card card-body">
                    <div class="subheader">{{ $label }}</div>
                    <div class="h1 mb-0">{{ number_format($value) }}</div>
                </div>
            </div>
        @endforeach
    </div>
    @if ($topReferrers->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><h2 class="card-title">{{ __('Top referrers') }}</h2></div>
            <div class="card-body d-flex flex-wrap gap-4">
                @foreach ($topReferrers as $referrer)
                    <span>{{ $referrer->full_name }} <span class="badge bg-primary-lt">{{ number_format($referrer->accepted_referrals_count) }}</span></span>
                @endforeach
            </div>
        </div>
    @endif
    <livewire:datatables.referrals />
</x-layouts::dashboard>
