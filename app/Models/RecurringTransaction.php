<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceNature;
use App\Enums\TransactionType;
use App\Notifications\ConfirmRecurringAmountNotification;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class RecurringTransaction extends Model
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
        'nature',
        'title',
        'amount',
        'frequency',
        'starts_on',
        'ends_on',
        'next_due_on',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => TransactionType::class,
        'nature' => RecurrenceNature::class,
        'frequency' => RecurrenceFrequency::class,
        'amount' => 'decimal:2',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'next_due_on' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the validation rules for a recurring transaction of the given user, shared by the website and the API.
     */
    public static function rules(User $user): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'finance_category_id' => ['required', FinanceCategory::existsFor($user)],
            'nature' => ['required', Rule::enum(RecurrenceNature::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'frequency' => ['required', Rule::enum(RecurrenceFrequency::class)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the user that owns the recurring transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the recurring transaction, even if it was deleted later.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id')->withTrashed();
    }

    /**
     * Get the transactions posted from this recurring transaction.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the amount spread over an average month, to compare weekly, monthly and yearly items.
     */
    public function monthlyAmount(): float
    {
        return (float) $this->amount * $this->frequency->timesPerMonth();
    }

    /**
     * Scope the query to the active recurring transactions that are due by today.
     */
    public function scopeDue(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereNotNull('next_due_on')
            ->where('next_due_on', '<=', today());
    }

    /**
     * Get the first occurrence on or after the given date, or null when the recurrence has ended by then.
     */
    public function occurrenceFrom(CarbonInterface $date): ?CarbonInterface
    {
        $n = 0;

        do {
            $occurrence = $this->frequency->occurrence($this->starts_on, $n++);
        } while ($occurrence->lt($date));

        return $this->ends_on && $occurrence->gt($this->ends_on) ? null : $occurrence;
    }

    /**
     * Schedule the next due date from today, past occurrences are left for the user to add by hand.
     *
     * Starts after the last posted occurrence, so editing never posts the same day twice.
     */
    public function schedule(): static
    {
        $from = today();
        $lastPosted = $this->exists ? $this->transactions()->withTrashed()->max('occurred_on') : null;

        if ($lastPosted && $from->lte($lastPosted)) {
            $from = Carbon::parse($lastPosted)->addDay();
        }

        $this->next_due_on = $this->occurrenceFrom($from);

        return $this;
    }

    /**
     * Post a transaction for every occurrence that is due by today, and move on to the next one.
     *
     * Fixed amounts are posted as confirmed, variable ones as pending until the user confirms the actual amount.
     */
    public function postDue(): int
    {
        $posted = 0;

        while ($this->is_active && $this->next_due_on && $this->next_due_on->lte(today())) {
            $transaction = $this->transactions()->create([
                'user_id' => $this->user_id,
                'finance_category_id' => $this->finance_category_id,
                'type' => $this->type,
                'amount' => $this->amount,
                'occurred_on' => $this->next_due_on,
                'status' => $this->nature->transactionStatus(),
                'note' => $this->title,
            ]);

            if ($this->nature === RecurrenceNature::Variable) {
                $this->user->notify(new ConfirmRecurringAmountNotification($transaction));
            }

            $this->next_due_on = $this->occurrenceFrom($this->next_due_on->copy()->addDay());
            $posted++;
        }

        $this->save();

        return $posted;
    }
}
