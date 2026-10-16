<?php

namespace App\Livewire\Datatables;

use App\Models\StaticPage;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\BulkAction;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\TernaryFilter;

class StaticPages extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return StaticPage::query();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('title', __('Title'))
                ->sortable()
                ->searchable(),
            TernaryColumn::make('active', __('Active')),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return [
            Action::view('dashboard.static-pages.show')->visible(route_allowed('dashboard.static-pages.show')),
            Action::edit('dashboard.static-pages.edit')->visible(route_allowed('dashboard.static-pages.edit')),
            Action::delete('dashboard.static-pages.destroy')->visible(route_allowed('dashboard.static-pages.destroy')),
        ];
    }

    /**
     * Get the bulk actions for the datatable.
     */
    public function bulkActions(): array
    {
        return [
            BulkAction::delete()
                ->visible(route_allowed('dashboard.static-pages.destroy'))
                ->success(fn () => $this->toastify()->success(__('Selected :resource have been deleted.', ['resource' => __('Pages')]))),
        ];
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            TernaryFilter::make('active', __('Active')),
        ];
    }
}
