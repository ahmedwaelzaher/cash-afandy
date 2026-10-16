<?php

namespace App\Livewire\Datatables;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;

class Admins extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Admin::whereNotCurrentAdmin();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make()
                ->getter(fn ($value, Admin $admin) => component('avatar', ['name' => $admin->name, 'image' => $admin->profile_picture]))
                ->exportable(false)
                ->html(),
            TextColumn::make('name', __('Name'))
                ->width('100%', min: '300px')
                ->searchable()
                ->sortable(),
            TextColumn::make(label: __('Role'))
                ->getter(fn ($value, $admin) => $admin->getRoleNames()->first()),
            TextColumn::make('email', __('Email'))
                ->email()
                ->searchable(),
            TernaryColumn::make('active', __('Active')),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::view('dashboard.admins.show')->visible(route_allowed('dashboard.admins.show')),
            Action::edit('dashboard.admins.edit')->visible(route_allowed('dashboard.admins.edit')),
            Action::delete('dashboard.admins.destroy')->visible(route_allowed('dashboard.admins.destroy')),
            Action::make(__('Impersonate'), 'ti ti-spy')
                ->visible(route_allowed('dashboard.impersonate-admins.create'))
                ->condition(fn ($admin) => $admin->active)
                ->route('dashboard.impersonate-admins.store', method: 'post', bounded: false)
                ->body(['admin_id' => fn ($admin) => $admin->id])
                ->confirmable(message: __('Are you sure you want to impersonate this admin?')),
        ]);
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('name', __('Name')),
            StringFilter::make('email', __('Email')),
            TernaryFilter::make('active', __('Active')),
        ];
    }
}
