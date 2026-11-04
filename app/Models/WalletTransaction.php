<?php

namespace App\Models;

use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'reference', 'wallet_id', 'type', 'amount', 'balance_after',
        'source_type', 'source_id', 'admin_id', 'description', 'idempotency_key',
    ];

    protected $hidden = ['idempotency_key'];

    protected $casts = [
        'type' => WalletTransactionType::class,
        'amount' => 'integer',
        'balance_after' => 'integer',
        'source_id' => 'integer',
        'admin_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Posted wallet transactions cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Posted wallet transactions cannot be deleted.'));
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source_type === (new Admin)->getMorphClass()
            ? __('Administrative adjustment') : class_basename($this->source_type);
    }
}
