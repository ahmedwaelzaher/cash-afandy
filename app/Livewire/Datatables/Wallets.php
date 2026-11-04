<?php

namespace App\Livewire\Datatables;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;

class Wallets extends Datatable
{
    public function query(): Builder
    {
        abort_unless(route_allowed('dashboard.wallets.index'), 403);

        return User::withTrashed()->with('wallet');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('full_name', __('Name'))->searchable()->sortable(),
            TextColumn::make('email', __('Email'))->searchable()->email(),
            TextColumn::make('wallet.balance', __('Available balance'))
                ->getter(fn ($value) => Wallet::formatAmount($value ?? 0)),
            TextColumn::make('wallet.currency', __('Currency')),
        ];
    }

    public function filters(): array
    {
        return [StringFilter::make('full_name', __('Name')), StringFilter::make('email', __('Email'))];
    }

    public function actions(): array
    {
        return ActionGroup::auto([
            Action::view('dashboard.wallets.show')->visible(route_allowed('dashboard.wallets.show')),
        ]);
    }
}
