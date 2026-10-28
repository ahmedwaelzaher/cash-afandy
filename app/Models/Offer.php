<?php

namespace App\Models;

use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'code',
        'store_id',
        'representative_id',
        'description',
        'tips',
        'fixed_discount',
        'discount',
        'minimum_amount',
        'maximum_amount',
        'minimum_usages',
        'maximum_usages',
        'launch_date',
        'expiration_date',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fixed_discount' => 'boolean',
        'launch_date' => 'datetime',
        'expiration_date' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'title',
        'description',
        'tips',
    ];

    /**
     * Get the table schema for query builder filters.
     *
     * @return array<string, array{title: string, type: string}>
     */
    public static function getTableSchema(): array
    {
        return [
            'id' => [
                'title' => __('ID'),
                'type' => 'integer',
            ],
            'code' => [
                'title' => __('Code'),
                'type' => 'string',
            ],
            'discount' => [
                'title' => __('Discount'),
                'type' => 'float',
            ],
            'active' => [
                'title' => __('Active'),
                'type' => 'boolean',
            ],
            'launch_date' => [
                'title' => __('Launch Date'),
                'type' => 'datetime',
            ],
            'expiration_date' => [
                'title' => __('Expiration Date'),
                'type' => 'datetime',
            ],
            'created_at' => [
                'title' => __('Created At'),
                'type' => 'datetime',
            ],
            'updated_at' => [
                'title' => __('Updated At'),
                'type' => 'datetime',
            ],
        ];
    }

    /** Include expired offers in saved lists, while keeping unpublished offers private. */
    public function scopePublished(Builder $query): void
    {
        $query->where('active', true)
            ->where('launch_date', '<=', now())
            ->whereHas('store', fn (Builder $query) => $query->where('active', true));
    }

    /**
     * Scope a query to only include offers available in the current market.
     */
    public function scopeAvailable(Builder $query): void
    {
        $country = website_country();

        $query->published()
            ->where(fn (Builder $query) => $query->whereNull('expiration_date')->orWhere('expiration_date', '>', now()))
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)));
    }

    public function scopeWithFavoriteState(Builder $query): void
    {
        if ($userId = auth('users')->id()) {
            $query->withExists(['favoritedBy as is_favorited' => fn (Builder $query) => $query->where('users.id', $userId)]);
        }
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Get the store that owns the offer.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the representative that owns the offer.
     */
    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class);
    }

    /**
     * Get the countries the offer is available in.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }
}
