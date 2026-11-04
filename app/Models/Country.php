<?php

namespace App\Models;

use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'native',
        'phone',
        'continent',
        'capital',
        'currency',
        'languages',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'languages' => 'array',
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
            'name' => [
                'title' => __('Name'),
                'type' => 'string',
            ],
            'native' => [
                'title' => __('Native'),
                'type' => 'string',
            ],
            'phone' => [
                'title' => __('Phone'),
                'type' => 'string',
            ],
            'continent' => [
                'title' => __('Continent'),
                'type' => 'string',
            ],
            'capital' => [
                'title' => __('Capital'),
                'type' => 'string',
            ],
            'currency' => [
                'title' => __('Currency'),
                'type' => 'string',
            ],
            'languages' => [
                'title' => __('Languages'),
                'type' => 'string',
            ],
        ];
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function scopeForAccounts(Builder $query): void
    {
        $query->whereIn('code', setting('website_countries'));
    }

    /**
     * Get country by code.
     */
    public static function code(string $code): ?Country
    {
        return static::where('code', $code)->first();
    }

    public function notificationTimezone(): ?string
    {
        if (! preg_match('/^[a-z]{2}$/i', $this->code)) return null;
        $zones = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, strtoupper($this->code));

        // Country alone cannot identify the user's timezone in a country with multiple zones.
        return count($zones) === 1 ? $zones[0] : null;
    }
}
