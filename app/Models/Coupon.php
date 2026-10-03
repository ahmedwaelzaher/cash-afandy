<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
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
        'client_id',
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

    /**
     * Scope a query to only include coupons visible on the website.
     */
    public function scopeAvailable(Builder $query): void
    {
        $country = website_country();

        $query->where('active', true)
            ->where('launch_date', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('expiration_date')->orWhere('expiration_date', '>', now()))
            ->whereHas('client', fn (Builder $query) => $query->where('active', true))
            ->when($country, fn (Builder $query) => $query->whereHas('countries', fn (Builder $query) => $query->whereKey($country->id)));
    }

    /**
     * Get the client that owns the coupon.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the representative that owns the coupon.
     */
    public function representative(): BelongsTo
    {
        return $this->belongsTo(Representative::class);
    }

    /**
     * Get the countries the coupon is available in.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }
}
