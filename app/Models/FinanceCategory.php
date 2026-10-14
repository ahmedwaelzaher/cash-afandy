<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Spatie\Translatable\HasTranslations;

class FinanceCategory extends Model
{
    use HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'icon',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => TransactionType::class,
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'title',
    ];

    /**
     * Get the user that owns the category.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope the query to the default (system) categories.
     */
    public function scopeDefaults(Builder $query): void
    {
        $query->whereNull('user_id');
    }

    /**
     * Scope the query to the categories available to the given user.
     */
    public function scopeAvailableTo(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', $user->id));
    }

    /**
     * Get the validation rule for a category the given user can use: a default or their own, not deleted.
     */
    public static function existsFor(User $user): Exists
    {
        return Rule::exists('finance_categories', 'id')
            ->whereNull('deleted_at')
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id));
    }

    /**
     * Get the categories available to the given user, defaults last, grouped by type.
     */
    public static function groupedFor(User $user): Collection
    {
        return static::availableTo($user)
            ->orderByRaw('user_id is null')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (FinanceCategory $category) => $category->type->value);
    }

    /**
     * Determine whether the category is a default (system) category.
     */
    public function isDefault(): bool
    {
        return $this->user_id === null;
    }
}
