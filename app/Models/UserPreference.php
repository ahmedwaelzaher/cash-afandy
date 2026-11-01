<?php

namespace App\Models;

use Database\Factories\UserPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    /** @use HasFactory<UserPreferenceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'theme',
        'language',
        'country_id',
        'notifications_enabled',
        'notification_types',
    ];

    /**
     * Keep the internal shopping-country key out of serialized preferences.
     */
    protected $hidden = ['country_id'];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'notification_types' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the user that owns the preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
