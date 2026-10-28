<?php

namespace App\Livewire\Datatables;

use App\Models\Cashback;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\NumericColumn;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\NumberFilter;
use Redot\Datatables\Filters\TernaryFilter;

class Cashbacks extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Cashback::query()->with(['store', 'representative', 'country']);
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('store.title', __('Store'))
                ->sortable(),
            TextColumn::make('country.name', __('Country'))
                ->sortable(),
            NumericColumn::make('percentage', __('Percentage'))
                ->sortable(),
            TextColumn::make('representative.full_name', __('Representative'))
                ->sortable(),
            TernaryColumn::make('active', __('Active')),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::edit('dashboard.cashbacks.edit'),
            Action::delete('dashboard.cashbacks.destroy'),
        ]);
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            NumberFilter::make('percentage', __('Percentage')),
            TernaryFilter::make('active', __('Active')),
        ];
    }
}
