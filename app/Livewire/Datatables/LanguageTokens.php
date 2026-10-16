<?php

namespace App\Livewire\Datatables;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;
use Redot\Models\Language;
use Redot\Models\LanguageToken;

class LanguageTokens extends Datatable
{
    /**
     * Sort column for the datatable.
     */
    #[Url]
    public string $sortColumn = 'key';

    /**
     * The default per page options.
     */
    public array $perPageOptions = [5, 10, 25, 50, 100, 250, 500, 1000];

    /**
     * The associated language model.
     */
    public Language $language;

    /**
     * Create a new component instance.
     */
    public function mount(Language $language)
    {
        $this->language = $language;
    }

    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return LanguageToken::where('language_id', $this->language->id)
            ->select('*')->selectRaw('value != original_translation as is_modified');
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('key', __('Key'))
                ->truncate(50)
                ->searchable()
                ->sortable(),
            TextColumn::make('value', __('Value'))
                ->truncate(50)
                ->searchable()
                ->sortable(),
            TernaryColumn::make('is_published', __('Published')),
            TernaryColumn::make('is_modified', __('Modified')),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return [
            Action::edit('dashboard.languages.tokens.edit')
                ->lightbox()
                ->parameters(['language' => $this->language])
                ->visible(route_allowed('dashboard.languages.tokens.edit')),
        ];
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('key', __('Key')),
            StringFilter::make('value', __('Value')),
            TernaryFilter::make('is_published', __('Published')),
        ];
    }
}
