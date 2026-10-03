<?php

namespace App\Livewire\Datatables;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Columns\ColorColumn;
use Redot\Datatables\Columns\IconColumn;
use Redot\Datatables\Columns\StatusColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\SelectFilter;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TrashedFilter;

class FinanceCategories extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return FinanceCategory::query()->defaults();
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
            StatusColumn::make('type', __('Type'))
                ->labels(TransactionType::values())
                ->classes([
                    TransactionType::Income->value => 'text-success',
                    TransactionType::Expense->value => 'text-danger',
                ]),
            ColorColumn::make('color', __('Color')),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return Datatable::defaultActionGroup([
            Action::edit('dashboard.finance-categories.edit')->visible(route_allowed('dashboard.finance-categories.edit'))->condition(fn (FinanceCategory $category) => ! $category->trashed()),
            Action::delete('dashboard.finance-categories.destroy')->visible(route_allowed('dashboard.finance-categories.destroy'))->condition(fn (FinanceCategory $category) => ! $category->trashed()),
            Action::restore('dashboard.finance-categories.restore')->visible(route_allowed('dashboard.finance-categories.restore'))->condition(fn (FinanceCategory $category) => $category->trashed()),
        ]);
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('title', __('Title')),
            SelectFilter::make('type', __('Type'))
                ->options(TransactionType::values()),
            TrashedFilter::make(),
        ];
    }
}
