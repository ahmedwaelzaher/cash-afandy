<?php

namespace App\Livewire\Datatables;

use App\Models\Broker;
use App\Models\Representative;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;

class Representatives extends Datatable
{
    /**
     * The associated broker model.
     */
    public Broker $broker;

    /**
     * Create a new component instance.
     */
    public function mount(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Representative::where('broker_id', $this->broker->id);
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('full_name', __('Name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('email', __('Email'))
                ->searchable()
                ->sortable(),
            TextColumn::make('phone', __('Phone'))
                ->searchable()
                ->sortable(),
            TextColumn::make('stores', __('No. Stores'))
                ->getter(fn ($value) => count($value ?? [])),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::edit('dashboard.brokers.representatives.edit')
                ->parameters(['broker' => $this->broker])
                ->visible(route_allowed('dashboard.brokers.representatives.edit')),
            Action::delete('dashboard.brokers.representatives.destroy')
                ->parameters(['broker' => $this->broker])
                ->visible(route_allowed('dashboard.brokers.representatives.destroy')),
        ]);
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('full_name', __('Name')),
            StringFilter::make('email', __('Email')),
            StringFilter::make('phone', __('Phone')),
        ];
    }
}
