<?php

namespace App\Livewire\Datatables;

use App\Models\ShortenedUrl;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Actions\BulkAction;
use Redot\Datatables\Columns\NumericColumn;
use Redot\Datatables\Columns\TagsColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\NumberFilter;
use Redot\Datatables\Filters\StringFilter;

class ShortenedUrls extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return ShortenedUrl::query();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('title', __('Title'))
                ->width('100%', min: '300px')
                ->searchable()
                ->sortable(),
            TextColumn::make('slug', __('Shortened Url'))
                ->route('website.shortened-urls.show', target: '_blank')
                ->searchable(),
            NumericColumn::make('clicks', __('Clicks'))
                ->sortable(),
            TagsColumn::make('tags', __('Tags'))
                ->searchable(),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::edit('dashboard.shortened-urls.edit'),
            Action::delete('dashboard.shortened-urls.destroy'),
        ]);
    }

    /**
     * Get the bulk actions for the datatable.
     */
    public function bulkActions(): array
    {
        return [
            BulkAction::delete()
                ->success(fn () => $this->toastify()->success(__('Selected :resource have been deleted.', ['resource' => __('Shortened URLs')]))),
        ];
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('title', __('Title')),
            StringFilter::make('slug', __('Shortened Url')),
            NumberFilter::make('clicks', __('Clicks')),
            StringFilter::make('tags', __('Tags')),
        ];
    }
}
