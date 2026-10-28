<?php

namespace App\Models;

use App\Enums\Gender;
use App\Notifications\NewUserNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;
use Redot\Traits\HasSessions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasRoles, HasSessions, Notifiable, SoftDeletes;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'birthdate',
        'gender',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'country_id',
        'state_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'birthdate' => 'date',
        'gender' => Gender::class,
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
            'first_name' => [
                'title' => __('First name'),
                'type' => 'string',
            ],
            'last_name' => [
                'title' => __('Last name'),
                'type' => 'string',
            ],
            'full_name' => [
                'title' => __('Full Name'),
                'type' => 'string',
            ],
            'email' => [
                'title' => __('Email'),
                'type' => 'string',
            ],
            'email_verified_at' => [
                'title' => __('Email Verified At'),
                'type' => 'datetime',
            ],
            'last_login_at' => [
                'title' => __('Last Login At'),
                'type' => 'datetime',
            ],
            'deleted_at' => [
                'title' => __('Deleted At'),
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
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->preferences()->create();
        });
    }

    /**
     * Get the user's preferences.
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function favoriteOffers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class)->withTimestamps();
    }

    /**
     * Get the validation rules for the user's personal details.
     *
     * Shared by registration and profile update.
     */
    public static function personalDetailsRules(bool $requireGender = false): array
    {
        return [
            // Stored in international format (+201001234567), the format the phone input reads back.
            'phone' => ['nullable', 'string', 'max:20', 'starts_with:+', 'phone'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'gender' => [$requireGender ? 'required' : 'nullable', Rule::enum(Gender::class)],
        ];
    }

    /**
     * Create a user and send their initial credentials.
     */
    public static function invite(array $attributes): static
    {
        $password = Str::random(8);
        $user = static::create([...$attributes, 'password' => $password]);

        $user->notify(new NewUserNotification($password));

        return $user;
    }
}
