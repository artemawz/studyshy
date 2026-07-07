<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoardPost extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'title',
        'body',
        'comments_read_at',
    ];

    protected function casts(): array
    {
        return [
            'comments_read_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BoardComment::class);
    }

    /** Neuester Kommentar – für die "ungelesen"-Erkennung der/des Ersteller:in. */
    public function latestComment(): HasOne
    {
        return $this->hasOne(BoardComment::class)->latestOfMany();
    }
}
