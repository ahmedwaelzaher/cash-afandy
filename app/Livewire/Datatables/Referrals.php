<?php

namespace App\Livewire\Datatables;

use App\Models\Referral;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Columns\DateColumn;
use Redot\Datatables\Columns\TernaryColumn;
use Redot\Datatables\Columns\TextColumn;
use Redot\Datatables\Datatable;
use Redot\Datatables\Filters\DateFilter;
use Redot\Datatables\Filters\StringFilter;
use Redot\Datatables\Filters\TernaryFilter;

class Referrals extends Datatable
{
    #[Locked]
    public ?int $userId = null;

    public function query(): Builder
    {
        abort_unless(route_allowed('dashboard.referrals.index'), 403);

        return Referral::with(['referrer', 'referredUser'])
            ->when($this->userId, fn (Builder $query) => $query->where('referrer_id', $this->userId))
            ->orderByDesc('registered_at');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('referrer.full_name', __('Invited by'))->searchable(),
            TextColumn::make('referredUser.full_name', __('Invited user'))->searchable(),
            TernaryColumn::make('referredUser.email_verified_at', __('Email verified now')),
            TernaryColumn::make('accepted', __('Status'))->true(__('Accepted'))->false(__('Rejected')),
            DateColumn::make('registered_at', __('Registered At'))->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            StringFilter::make('referrer.full_name', __('Invited by')),
            TernaryFilter::make('accepted', __('Status'))->labels(yes: __('Accepted'), no: __('Rejected')),
            DateFilter::make('registered_at', __('Registered At')),
        ];
    }

    public function actions(): array
    {
        return ActionGroup::auto([
            Action::make(__('Reject'), 'ti ti-x')
                ->visible(route_allowed('dashboard.referrals.update'))
                ->condition(fn (Referral $referral) => $referral->accepted)
                ->route('dashboard.referrals.update', method: 'put')->body(['accepted' => 0])->confirmable(),
            Action::make(__('Accept'), 'ti ti-check')
                ->visible(route_allowed('dashboard.referrals.update'))
                ->condition(fn (Referral $referral) => ! $referral->accepted)
                ->route('dashboard.referrals.update', method: 'put')->body(['accepted' => 1])->confirmable(),
        ]);
    }
}
