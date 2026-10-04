<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Transaction extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'finance_category_id',
        'type',
        'amount',
        'occurred_on',
        'status',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'amount' => 'decimal:2',
        'occurred_on' => 'date',
    ];

    /**
     * Get the validation rules for a transaction of the given user, shared by the website and the API.
     */
    public static function rules(User $user): array
    {
        return [
            'finance_category_id' => [
                'required',
                Rule::exists('finance_categories', 'id')
                    ->whereNull('deleted_at')
                    ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id)),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'occurred_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the validation rules for the listing filters.
     */
    public static function filterRules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'category' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    /**
     * Fill the missing period of the given filters with the current month, the period people check the most.
     */
    public static function withDefaultPeriod(array $filters): array
    {
        $filters['from'] ??= now()->startOfMonth()->toDateString();
        $filters['to'] ??= now()->endOfMonth()->toDateString();

        return $filters;
    }

    /**
     * Get the income, expense and net totals of the given transactions query.
     *
     * @return array{income: float, expense: float, net: float}
     */
    public static function totals(Builder $query): array
    {
        $totals = (clone $query)->toBase()->reorder()
            ->selectRaw('coalesce(sum(case when type = ? then amount end), 0) as income', [TransactionType::Income->value])
            ->selectRaw('coalesce(sum(case when type = ? then amount end), 0) as expense', [TransactionType::Expense->value])
            ->first();

        return [
            'income' => (float) $totals->income,
            'expense' => (float) $totals->expense,
            'net' => (float) $totals->income - (float) $totals->expense,
        ];
    }

    /**
     * Scope the query to the confirmed transactions matching the listing filters.
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query->confirmed()
            ->between($filters['from'] ?? null, $filters['to'] ?? null)
            ->when($filters['type'] ?? null, fn (Builder $query, $type) => $query->where('type', $type))
            ->when($filters['category'] ?? null, fn (Builder $query, $category) => $query->where('finance_category_id', $category));
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the transaction, even if it was deleted later.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id')->withTrashed();
    }

    /**
     * Scope the query to the confirmed transactions, the only ones counted in totals.
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', TransactionStatus::Confirmed);
    }

    /**
     * Scope the query to the transactions that occurred within the given dates.
     */
    public function scopeBetween(Builder $query, ?string $from, ?string $to): void
    {
        $query->when($from, fn (Builder $query) => $query->where('occurred_on', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('occurred_on', '<=', $to));
    }
}
