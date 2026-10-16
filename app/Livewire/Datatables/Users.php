<?php

namespace App\Livewire\Datatables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Actions\BulkAction;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;
use Redot\Datatables\Filters\TrashedFilter;

class Users extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return User::query();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('full_name', __('Name'))
                ->width('100%')
                ->minWidth('300px')
                ->searchable()
                ->sortable(),
            TextColumn::make('email', __('Email'))
                ->width('300px')
                ->email()
                ->searchable(),
            TernaryColumn::make('email_verified_at', __('Verified')),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::view('dashboard.users.show')->visible(route_allowed('dashboard.users.show')),
            Action::edit('dashboard.users.edit')->visible(route_allowed('dashboard.users.edit')),
            Action::make(__('Impersonate'), 'ti ti-spy')
                ->visible(route_allowed('dashboard.impersonate-users.create'))
                ->condition(fn (User $user) => ! $user->trashed())
                ->route('dashboard.impersonate-users.store', method: 'post', bounded: false)
                ->body(['user_id' => fn (User $user) => $user->id])
                ->confirmable(message: __('Are you sure you want to impersonate this user?')),
            Action::delete('dashboard.users.destroy')->visible(route_allowed('dashboard.users.destroy'))->condition(fn (User $user) => ! $user->trashed()),
            Action::restore('dashboard.users.restore')->visible(route_allowed('dashboard.users.restore'))->condition(fn (User $user) => $user->trashed()),
        ]);
    }

    /**
     * Get the bulk actions for the datatable.
     */
    public function bulkActions(): array
    {
        return [
            BulkAction::delete()
                ->visible(route_allowed('dashboard.users.destroy'))
                ->success(fn () => $this->toastify()->success(__('Selected :resource have been deleted.', ['resource' => __('Users')]))),
            BulkAction::restore()
                ->visible(route_allowed('dashboard.users.restore'))
                ->success(fn () => $this->toastify()->success(__('Selected :resource have been restored.', ['resource' => __('Users')]))),
        ];
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('full_name', __('Name')),
            StringFilter::make('email', __('Email')),
            TernaryFilter::make(label: __('Verified'))
                ->queries(
                    yes: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                    no: fn (Builder $query) => $query->whereNull('email_verified_at')
                ),
            TrashedFilter::make(),
        ];
    }
}
