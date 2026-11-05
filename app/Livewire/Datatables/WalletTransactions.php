<?php

namespace App\Livewire\Datatables;

use App\Enums\WalletTransactionType;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\DateFilter;
use Redot\Datatables\Filters\StringFilter;

class WalletTransactions extends Datatable
{
    #[Locked]
    public int $userId;

    public function query(): Builder
    {
        abort_unless(route_allowed('dashboard.wallets.show'), 403);

        return WalletTransaction::with('admin')->whereHas('wallet', fn (Builder $query) => $query->where('user_id', $this->userId))->latest('id');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('reference', __('Reference'))->searchable(),
            TextColumn::make('type', __('Type'))->getter(fn (WalletTransactionType $value) => $value->label()),
            TextColumn::make('amount', __('Amount'))->getter(fn (int $value) => Wallet::formatAmount($value)),
            TextColumn::make('balance_after', __('Balance after'))->getter(fn (int $value) => Wallet::formatAmount($value)),
            TextColumn::make('source_type', __('Source'))
                ->getter(fn ($value, WalletTransaction $row) => $row->source_label . ' #' . $row->source_id),
            TextColumn::make('admin.name', __('Performed by')),
            TextColumn::make('description', __('Reason'))->nowrap(false),
            DateColumn::make('created_at', __('Created At'))->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            StringFilter::make('reference', __('Reference')),
            DateFilter::make('created_at', __('Created At')),
        ];
    }
}
