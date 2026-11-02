<?php

namespace App\Models;

use App\Enums\NotificationType;
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

    public function excludedNotificationTypes(): array
    {
        $types = $this->notification_types;
        if ($types === null) return [];

        // Previously this column contained selected types, when only General existed.
        if (array_is_list($types)) return array_values(array_diff([NotificationType::General->value], $types));

        return $types['disabled'] ?? [];
    }

    public function enabledNotificationTypes(): array
    {
        return array_values(array_diff(array_column(NotificationType::cases(), 'value'), $this->excludedNotificationTypes()));
    }

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
