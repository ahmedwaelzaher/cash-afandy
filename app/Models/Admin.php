<?php

namespace App\Models;

use App\Notifications\NewAdminNotification;
use Database\Factories\AdminFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Redot\Traits\HasSessions;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasRoles, HasSessions, Notifiable;

    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    /**
     * Roles live on the session guard. Without this, API requests
     * (admins-api) look up roles on a guard that has none.
     */
    protected $guard_name = 'admins';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'active',
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
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
        'last_login_at' => 'datetime',
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
            'name' => [
                'title' => __('Name'),
                'type' => 'string',
            ],
            'email' => [
                'title' => __('Email'),
                'type' => 'string',
            ],
            'active' => [
                'title' => __('Active'),
                'type' => 'boolean',
            ],
            'last_login_at' => [
                'title' => __('Last Login At'),
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
     * Create an admin and send their initial credentials.
     */
    public static function invite(array $attributes, ?string $role = null): static
    {
        $password = Str::random(8);
        $admin = static::create([...$attributes, 'password' => $password]);

        if (! empty($role)) {
            $admin->assignRole($role);
        }

        $admin->notify(new NewAdminNotification($password));

        return $admin;
    }

    /**
     * Scope a query to only include admins that are not the current admin.
     */
    public function scopeWhereNotCurrentAdmin($query): void
    {
        $query->where('id', '!=', auth('admins')->id());
    }

    /**
     * Scope a query to only include active admins.
     */
    public function scopeActive($query): void
    {
        $query->where('active', true);
    }

    /**
     * Scope a query to only include inactive admins.
     */
    public function scopeInactive($query): void
    {
        $query->where('active', false);
    }

    /**
     * Scope a query to only include admins that have logged in.
     */
    public function scopeLoggedIn($query): void
    {
        $query->whereNotNull('last_login_at');
    }

    /**
     * Scope a query to only include admins that have not logged in.
     */
    public function scopeNotLoggedIn($query): void
    {
        $query->whereNull('last_login_at');
    }
}
