<?php

namespace App\Models;

use App\Enums\Gender;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasRoles, Notifiable, SoftDeletes;

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
        'country_id',
        'state_id',
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
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return url(route('website.password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));
        });

        VerifyEmail::createUrlUsing(function (User $user) {
            return URL::temporarySignedRoute(
                'website.verification.verify',
                Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );
        });

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

    /**
     * Get the user's own finance categories.
     */
    public function financeCategories(): HasMany
    {
        return $this->hasMany(FinanceCategory::class);
    }

    /**
     * Get the user's income and expense transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the validation rules for the user's personal details.
     *
     * Shared by registration, profile completion and profile update.
     */
    public static function personalDetailsRules(mixed $countryId): array
    {
        return [
            // Stored in international format (+201001234567), the format the phone input reads back.
            'phone' => ['nullable', 'string', 'max:20', 'starts_with:+', 'phone'],
            'birthdate' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'country_id' => ['required', 'exists:countries,id'],
            'state_id' => ['required', Rule::exists('states', 'id')->where('country_id', $countryId)],
        ];
    }

    /**
     * Determine whether the user has filled in all the required personal details.
     */
    public function hasCompletedProfile(): bool
    {
        return filled($this->first_name) && filled($this->last_name)
            && $this->birthdate && $this->gender && $this->country_id && $this->state_id;
    }

    /**
     * Get the user's country of residence.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the user's state (governorate) of residence.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
