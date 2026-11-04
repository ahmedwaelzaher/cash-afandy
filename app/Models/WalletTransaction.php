<?php

namespace App\Models;

use App\Enums\WalletTransactionSource;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    /**
     * The name of the updated at column.
     *
     * @var string|null
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'source_category',
        'source_type',
        'source_id',
        'admin_id',
        'description',
        'idempotency_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'idempotency_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => WalletTransactionType::class,
        'source_category' => WalletTransactionSource::class,
        'amount' => 'integer',
        'balance_after' => 'integer',
        'source_id' => 'integer',
        'admin_id' => 'integer',
    ];

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the source of the transaction.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the administrator who performed the transaction.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the display label for the transaction source category.
     */
    public function getSourceLabelAttribute(): string
    {
        return $this->source_category->label();
    }
}
