<?php

namespace App\Models;

use App\Enums\ProgramType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Program extends Model
{
    use HasTranslations;

    /**
     * The attributes editable through program management.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'terms',
        'country_id',
        'currency',
        'type',
        'reward_amount',
        'repeatable',
        'max_subscriptions',
        'requires_approval',
        'active',
        'launched_at',
        'expired_at',
    ];

    /**
     * The program content available in each language.
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'title',
        'description',
        'terms',
    ];

    /**
     * The typed eligibility and reward attributes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ProgramType::class,
        'reward_amount' => 'integer',
        'repeatable' => 'boolean',
        'max_subscriptions' => 'integer',
        'requires_approval' => 'boolean',
        'active' => 'boolean',
        'launched_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * Select programs currently accepting subscriptions and activity.
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('active', true)->where('launched_at', '<=', now())
            ->where(fn (Builder $query) => $query->where('repeatable', true)->orWhere('expired_at', '>', now()));
    }

    /**
     * Determine whether new qualifying activity is currently allowed.
     */
    public function isAvailable(): bool
    {
        return $this->active && $this->launched_at->lte(now())
            && ($this->repeatable || $this->expired_at?->gt(now()));
    }

    /**
     * Get the program's eligible account country.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the explicit user subscriptions to this program.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProgramSubscription::class);
    }
}
