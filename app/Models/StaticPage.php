<?php

namespace App\Models;

use Database\Factories\StaticPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    /** @use HasFactory<StaticPageFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
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
        'content',
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
            'slug' => [
                'title' => __('Slug'),
                'type' => 'string',
            ],
            'active' => [
                'title' => __('Active'),
                'type' => 'boolean',
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
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $staticPage) {
            if (filled($staticPage->slug)) return;

            $title = $staticPage->getTranslation('title', config('app.fallback_locale'));
            $slug = Str::slug($title);

            while (self::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . Str::random(8);
            }

            $staticPage->slug = $slug;
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope a query to only include active pages.
     */
    public function scopeActive($query): void
    {
        $query->where('active', true);
    }

    /**
     * Scope a query to only include inactive pages.
     */
    public function scopeInactive($query): void
    {
        $query->where('active', false);
    }
}
