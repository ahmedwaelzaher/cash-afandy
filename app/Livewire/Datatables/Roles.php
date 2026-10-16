<?php

namespace App\Livewire\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Spatie\Permission\Models\Role;

class Roles extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Role::query();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('name', __('Name'))
                ->width('100%')
                ->searchable()
                ->sortable(),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::edit('dashboard.roles.edit')->visible(route_allowed('dashboard.roles.edit')),
            Action::delete('dashboard.roles.destroy')->visible(route_allowed('dashboard.roles.destroy')),
        ]);
    }
}
