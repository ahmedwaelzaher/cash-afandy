<?php

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Redot\Traits\UserAuditable;
use Spatie\Translatable\HasTranslations;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    use HasTranslations, UserAuditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'url',
        'logo',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
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
    ];

    /**
     * Get the table schema for query builder filters.
     *
     * @return array<string, array{title: string, type: string}>
     */
    public static function getTableSchema(): array
    {
        $admins = Admin::all()->pluck('name', 'id');

        return [
            'id' => [
                'title' => __('ID'),
                'type' => 'integer',
            ],
            'slug' => [
                'title' => __('Slug'),
                'type' => 'string',
            ],
            'url' => [
                'title' => __('URL'),
                'type' => 'string',
            ],
            'active' => [
                'title' => __('Active'),
                'type' => 'boolean',
            ],
            'created_by' => [
                'title' => __('Created By'),
                'type' => 'integer',
                'values' => $admins,
            ],
            'updated_by' => [
                'title' => __('Updated By'),
                'type' => 'integer',
                'values' => $admins,
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
     * Get the categories that belong to the store.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get the offers belonging to the store.
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * Get the countries that belong to the store.
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
