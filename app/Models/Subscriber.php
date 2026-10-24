<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = [
        'email',
    ];

    /**
     * Get the user associated with the subscriber if exists.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'email', 'email');
    }
}
