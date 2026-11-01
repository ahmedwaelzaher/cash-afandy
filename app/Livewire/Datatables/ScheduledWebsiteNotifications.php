<?php

namespace App\Livewire\Datatables;

use App\Models\ScheduledWebsiteNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\DateFilter;
use Redot\Datatables\Filters\StringFilter;

class ScheduledWebsiteNotifications extends Datatable
{
    public function query(): Builder
    {
        return ScheduledWebsiteNotification::query()->pending()->orderBy('send_at');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('title', __('Title'))->searchable()->sortable(),
            TextColumn::make('body', __('Description'))
                ->getter(fn ($value) => Str::limit($value, 80)),
            TextColumn::make(label: __('Users'))
                ->getter(fn ($value, ScheduledWebsiteNotification $entry) => number_format(count($entry->recipient_ids))),
            DateColumn::make('send_at', __('Date'))->sortable(),
            DateColumn::make('created_at', __('Created At'))->sortable(),
        ];
    }

    public function actions(): array
    {
        return [
            Action::delete('dashboard.website-notifications.destroy')
                ->visible(route_allowed('dashboard.website-notifications.destroy')),
        ];
    }

    public function filters(): array
    {
        return [
            StringFilter::make('title', __('Title')),
            DateFilter::make('send_at', __('Date')),
        ];
    }
}
