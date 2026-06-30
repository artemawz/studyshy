<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCourse extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'degree',
        'semester',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
