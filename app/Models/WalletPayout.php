<?php

namespace App\Models;

use App\Enums\WalletPayoutMethod;
use App\Enums\WalletPayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletPayout extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'wallet_id',
        'method',
        'amount',
        'details',
        'status',
        'idempotency_key',
        'admin_id',
        'transfer_reference',
        'review_note',
        'reviewed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'details',
        'idempotency_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'method' => WalletPayoutMethod::class,
        'status' => WalletPayoutStatus::class,
        'amount' => 'integer',
        'details' => 'encrypted:array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns the payout request.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the administrator who reviewed the payout request.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
