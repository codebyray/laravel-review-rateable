<?php

namespace Codebyray\ReviewRateable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $casts = [
        'value' => 'integer',
    ];

    protected $fillable = [
        'review_id',
        'key',
        'value',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
