<?php

namespace App\Livewire\Datatables;

use App\Models\Memo;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Actions\BulkAction;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\IconColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\DateFilter;
use Redot\Datatables\Filters\StringFilter;

class Memos extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Memo::forAuthenticatedAdmin();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            IconColumn::make('icon'),
            TextColumn::make('title', __('Title'))
                ->width('100%', min: '300px')
                ->searchable()
                ->sortable(),
            DateColumn::make('date', __('Date'))
                ->width('200px')
                ->relative()
                ->sortable(),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::view('dashboard.memos.show'),
            Action::edit('dashboard.memos.edit'),
            Action::delete('dashboard.memos.destroy'),
        ]);
    }

    /**
     * Get the bulk actions for the datatable.
     */
    public function bulkActions(): array
    {
        return [
            BulkAction::delete()
                ->success(fn () => $this->toastify()->success(__('Selected :resource have been deleted.', ['resource' => __('Memos')]))),
        ];
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('title', __('Title')),
            DateFilter::make('date', __('Date')),
        ];
    }
}
