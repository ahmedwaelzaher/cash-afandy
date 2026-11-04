<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\NotificationType;
use App\Notifications\NewUserNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        'referral_code',
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
        static::creating(function (User $user) {
            do {
                $code = Str::upper(Str::random(10));
            } while (static::withTrashed()->where('referral_code', $code)->exists());

            $user->referral_code = $code;
        });

        static::created(function (User $user) {
            $user->preferences()->create();
        });

        static::updating(function (User $user) {
            if ($user->isDirty('country_id') && $user->getOriginal('country_id') && $user->wallet()->exists()) {
                throw ValidationException::withMessages(['country_id' => __('The account country cannot be changed after the wallet is created.')]);
            }
        });
    }

    /**
     * Get the user's preferences.
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_user_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public static function accountCountryRules(): array
    {
        return ['required', 'integer', Rule::in(Country::forAccounts()->pluck('id')->all())];
    }

    public function hasCompletedProfile(): bool
    {
        return filled($this->first_name) && filled($this->last_name) && $this->gender !== null
            && $this->country_id && $this->wallet()->exists();
    }

    public function initializeWallet(int $countryId): Wallet
    {
        $wallet = DB::transaction(function () use ($countryId) {
            $owner = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $country = Country::forAccounts()->find($countryId);
            if (! $country || ! preg_match('/^[A-Z]{3}$/D', $country->currency)) {
                throw ValidationException::withMessages(['country_id' => __('Please select a supported account country.')]);
            }
            $wallet = $owner->wallet()->lockForUpdate()->first();
            if ($wallet && $wallet->currency !== $country->currency) {
                throw ValidationException::withMessages(['country_id' => __('The selected country must match your existing wallet currency.')]);
            }
            $owner->forceFill(['country_id' => $country->id])->save();

            return $wallet ?? $owner->wallet()->create(['currency' => $country->currency]);
        }, 3);
        $this->refresh()->setRelation('wallet', $wallet);

        return $wallet;
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function acceptedReferrals(): HasMany
    {
        return $this->referrals()->accepted();
    }

    public function referralProgress(): array
    {
        $target = max(1, (int) setting('referral_reward_target'));
        $count = max(0, (int) ($this->accepted_referrals_count ?? $this->acceptedReferrals()->count()));

        return [
            'target' => $target,
            'count' => min($count, $target),
            'remaining' => max(0, $target - $count),
            'completed' => $count >= $target,
            'percentage' => min($count, $target) / $target * 100,
        ];
    }

    public function invitationUrl(): ?string
    {
        return $this->referral_code ? route('website.index', ['referral_code' => $this->referral_code]) : null;
    }

    public function receivesWebsiteNotification(?NotificationType $type = null): bool
    {
        $preferences = $this->preferences;

        // New types are enabled unless the account explicitly excluded them.
        return ! $this->trashed() && ($preferences?->notifications_enabled ?? true)
            && ($type === null || ! in_array($type->value, $preferences?->excludedNotificationTypes() ?? [], true));
    }

    public function offerNotificationEvents(): HasMany
    {
        return $this->hasMany(OfferNotificationEvent::class);
    }

    public function favoriteOffers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class)->withTimestamps();
    }

    public function pushSubscriptions(): MorphMany
    {
        return $this->morphMany(PushSubscription::class, 'notifiable');
    }

    public function notificationTimezone(?Country $defaultCountry = null): ?string
    {
        $browser = $this->pushSubscriptions->whereNull('disabled_at')->whereNotNull('country_id')
            ->sortByDesc(fn ($subscription) => [$subscription->last_seen_at?->timestamp ?? 0, $subscription->id])->first();

        return $browser?->country?->notificationTimezone()
            ?? $this->preferences?->country?->notificationTimezone()
            ?? ($defaultCountry ?? Country::code(setting('default_website_country')))?->notificationTimezone();
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
