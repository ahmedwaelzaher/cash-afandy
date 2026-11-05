<?php

namespace App\Livewire\Datatables;

use App\Enums\WalletPayoutMethod;
use App\Enums\WalletPayoutStatus;
use App\Models\Wallet;
use App\Models\WalletPayout;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;

class WalletPayouts extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        abort_unless(route_allowed('dashboard.wallet-payouts.index'), 403);

        return WalletPayout::with('wallet.user')->latest('id');
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('reference', __('Reference'))->searchable(),
            TextColumn::make('wallet.user.full_name', __('Name'))->searchable(),
            TextColumn::make('method', __('Payout method'))->getter(fn (WalletPayoutMethod $value) => $value->label()),
            TextColumn::make('amount', __('Amount'))->getter(fn (int $value) => Wallet::formatAmount($value)),
            TextColumn::make('wallet.currency', __('Currency')),
            TextColumn::make('status', __('Status'))->getter(fn (WalletPayoutStatus $value) => $value->label()),
            DateColumn::make('created_at', __('Created At'))->sortable(),
        ];
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('reference', __('Reference')),
            StringFilter::make('status', __('Status')),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return [
            Action::view('dashboard.wallet-payouts.show')->visible(route_allowed('dashboard.wallet-payouts.show')),
        ];
    }
}
