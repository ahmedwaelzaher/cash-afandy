<?php

namespace App\Livewire\Datatables;

use App\Models\FailedJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\DateFilter;
use Redot\Datatables\Filters\StringFilter;

class FailedJobs extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return FailedJob::query();
    }

    /**
     * Get the columns for the datatable.
     */
    public function columns(): array
    {
        return [
            TextColumn::make(label: __('Job'))
                ->getter(fn ($value, FailedJob $job) => $job->name)
                ->width('100%', min: '250px'),
            TextColumn::make('queue', __('Queue'))
                ->searchable()
                ->sortable(),
            TextColumn::make(label: __('Exception'))
                ->getter(fn ($value, FailedJob $job) => Str::limit($job->exception_message, 80)),
            DateColumn::make('failed_at', __('Failed At'))
                ->sortable(),
        ];
    }

    /**
     * Get the actions for the datatable.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::make(__('Retry'), 'ti ti-refresh')
                ->visible(route_allowed('dashboard.failed-jobs.retry'))
                ->route('dashboard.failed-jobs.retry', method: 'post')
                ->confirmable(message: __('Are you sure you want to retry this job?')),
            Action::delete('dashboard.failed-jobs.destroy')->visible(route_allowed('dashboard.failed-jobs.destroy')),
        ]);
    }

    /**
     * Get the filters for the datatable.
     */
    public function filters(): array
    {
        return [
            StringFilter::make('queue', __('Queue')),
            DateFilter::make('failed_at', __('Failed At')),
        ];
    }
}
