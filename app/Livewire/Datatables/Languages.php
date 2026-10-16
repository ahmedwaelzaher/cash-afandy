<?php

namespace App\Livewire\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Models\Language;

class Languages extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Language::query();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('code', __('Code'))
                ->width('100px')
                ->searchable()
                ->sortable(),
            TextColumn::make('is_rtl', __('Direction'))
                ->width('150px')
                ->getter(fn ($value) => $value ? __('Right to Left') : __('Left to Right'))
                ->sortable(),
            TextColumn::make('name', __('Name'))
                ->width('100%', min: '300px')
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
            Action::edit('dashboard.languages.edit')->visible(route_allowed('dashboard.languages.edit')),
            Action::delete('dashboard.languages.destroy')->visible(route_allowed('dashboard.languages.destroy')),
            Action::make(__('Tokens'), 'ti ti-language')
                ->route('dashboard.languages.tokens.index')
                ->visible(route_allowed('dashboard.languages.tokens.index')),
        ]);
    }
}
