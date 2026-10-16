<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
    ];

    /**
     * Attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'failed_at' => 'datetime',
    ];

    /**
     * Human-readable job name extracted from the payload.
     */
    protected function name(): Attribute
    {
        return Attribute::get(
            fn () => json_decode((string) $this->payload, true)['displayName'] ?? __('Unknown'),
        );
    }

    /**
     * First line of the stored exception (class and message).
     */
    protected function exceptionMessage(): Attribute
    {
        return Attribute::get(
            fn () => str($this->exception)->before("\n")->value(),
        );
    }
}
