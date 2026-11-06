<?php

namespace App\Livewire\Datatables;

use App\Models\Program;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\TernaryFilter;

class Programs extends Datatable
{
    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return Program::query()->with('country')->withCount('subscriptions');
    }

    /**
     * Display eligibility, rewards and program availability.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('title', __('Title')),
            TextColumn::make('country.name', __('Country')),
            TextColumn::make('max_subscriptions', __('Maximum subscriptions per user')),
            TextColumn::make('reward_amount', __('Reward'))->getter(fn (int $value, Program $row) => Wallet::formatAmount($value) . ' ' . $row->currency),
            TextColumn::make('subscriptions_count', __('Subscriptions')),
            TernaryColumn::make('repeatable', __('Repeatable')),
            TernaryColumn::make('requires_approval', __('Requires administrative approval')),
            TernaryColumn::make('active', __('Active')),
            DateColumn::make('expired_at', __('Expiration Date')),
        ];
    }

    /**
     * Reuse permission-aware resource actions.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::make(__('Subscriptions'), 'ti ti-users')->visible(route_allowed('dashboard.programs.show'))->route('dashboard.programs.show'),
            Action::make(__('Program rewards'), 'ti ti-gift')
                ->visible(route_allowed('dashboard.program-rewards.index'))
                ->route('dashboard.program-rewards.index', ['program_id' => fn (Program $program) => $program->id], bounded: false),
            Action::edit('dashboard.programs.edit'),
        ]);
    }

    /**
     * Filter programs by publication state.
     */
    public function filters(): array
    {
        return [TernaryFilter::make('active', __('Active'))];
    }
}
