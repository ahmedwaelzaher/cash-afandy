<?php

namespace App\Livewire\Datatables;

use App\Enums\ProgramRewardStatus;
use App\Models\ProgramReward;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\StringFilter;

class ProgramRewards extends Datatable
{
    /**
     * Limit rewards to a selected program when embedded in its details.
     */
    #[Locked]
    public ?int $programId = null;

    /**
     * Get the query source of the datatable.
     */
    public function query(): Builder
    {
        return ProgramReward::query()->with(['subscription.program', 'subscription.user'])
            ->when($this->programId, fn (Builder $query) => $query->whereHas('subscription', fn (Builder $query) => $query->where('program_id', $this->programId)));
    }

    /**
     * Display each reward's owner, program, amount and review status.
     */
    public function columns(): array
    {
        return [
            TextColumn::make('subscription.program.title', __('Program')),
            TextColumn::make('subscription.user.full_name', __('User')),
            TextColumn::make('program_subscription_id', __('Subscription')),
            TextColumn::make('amount', __('Reward'))->getter(fn (int $value, ProgramReward $row) => Wallet::formatAmount($value) . ' ' . $row->currency),
            TextColumn::make('status', __('Status'))->getter(fn (ProgramRewardStatus $value) => $value->label()),
            DateColumn::make('created_at', __('Created At')),
        ];
    }

    /**
     * Link to evidence and review controls for authorized administrators.
     */
    public function actions(): array
    {
        return ActionGroup::auto([
            Action::make(__('Details'), 'ti ti-eye')->visible(route_allowed('dashboard.program-rewards.show'))->route('dashboard.program-rewards.show'),
        ]);
    }

    /**
     * Filter rewards by stored review status.
     */
    public function filters(): array
    {
        return [StringFilter::make('status', __('Status'))];
    }
}
