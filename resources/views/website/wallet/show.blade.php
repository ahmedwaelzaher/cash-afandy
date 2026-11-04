<x-layouts::website :title="__('Afandy Wallet')">
    <section class="py-4" aria-labelledby="wallet-title">
        <div class="container site-container">
            <h1 class="display-6 fw-bold mb-4" id="wallet-title">{{ __('Afandy Wallet') }}</h1>
            <div class="card card-body mb-4">
                <div class="subheader">{{ __('Available balance') }}</div>
                <div class="h1 mb-0"><bdi dir="ltr">{{ \App\Models\Wallet::formatAmount($wallet?->balance ?? 0) }} {{ $wallet?->currency ?? '-' }}</bdi></div>
            </div>

            @if ($transactions?->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Wallet transactions') }}</h2></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Reference') }}</th>
                                    <th scope="col">{{ __('Type') }}</th>
                                    <th scope="col">{{ __('Amount') }}</th>
                                    <th scope="col">{{ __('Balance after') }}</th>
                                    <th scope="col">{{ __('Source') }}</th>
                                    <th scope="col">{{ __('Reason') }}</th>
                                    <th scope="col">{{ __('Created At') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td><bdi dir="ltr">{{ $transaction->reference }}</bdi></td>
                                        <td>{{ $transaction->type->label() }}</td>
                                        <td><bdi dir="ltr">{{ \App\Models\Wallet::formatAmount($transaction->amount) }}</bdi></td>
                                        <td><bdi dir="ltr">{{ \App\Models\Wallet::formatAmount($transaction->balance_after) }}</bdi></td>
                                        <td>{{ $transaction->source_label }}</td>
                                        <td>{{ $transaction->description }}</td>
                                        <td class="text-nowrap"><bdi dir="ltr">{{ $transaction->created_at->format('Y-m-d H:i') }}</bdi></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($transactions->hasPages())
                    <div class="mt-4">{{ $transactions->links() }}</div>
                @endif
            @else
                <x-empty icon="ti ti-wallet" :title="__('No wallet transactions yet')" />
            @endif
        </div>
    </section>
</x-layouts::website>
