<?php

namespace App\Livewire\Datatables;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\NumericColumn;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\NumberFilter;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;

class Offers extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Offer::query()->with(['client', 'representative']);
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('title', __('Title'))
                ->searchable()
                ->sortable(),
            TextColumn::make('code', __('Code'))
                ->searchable()
                ->sortable(),
            TextColumn::make('client.title', __('Client'))
                ->sortable(),
            TextColumn::make('representative.full_name', __('Representative'))
                ->sortable(),
            NumericColumn::make('discount', __('Discount'))
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
            Action::edit('dashboard.offers.edit'),
            Action::delete('dashboard.offers.destroy'),
        ]);
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('title', __('Title')),
            StringFilter::make('code', __('Code')),
            NumberFilter::make('discount', __('Discount')),
            TernaryFilter::make('active', __('Active')),
        ];
    }
}
