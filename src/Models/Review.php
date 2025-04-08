<?php

namespace Codebyray\ReviewRateable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $fillable = [
        'reviewable_id',
        'reviewable_type',
        'user_id',
        'review',
        'department',
        'recommend',
        'approved',
    ];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function scopeDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

}
