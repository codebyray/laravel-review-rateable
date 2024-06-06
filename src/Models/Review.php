<?php

namespace Codebyray\ReviewRateable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $casts = [
        'ratings' => 'array',
        'departments' => 'array',
    ];

    protected $fillable = [
        'reviewrateable_id', 'reviewrateable_type', 'author_id', 'author_type',
        'ratings', 'departments', 'title', 'body', 'approved', 'recommend'
    ];

    public function reviewable(): MorphTo
    {
        return $this->morphTo('reviewrateable');
    }

    public function user(): MorphTo
    {
        return $this->morphTo('author');
    }

    public function setRatingsAttribute($value)
    {
        $this->attributes['ratings'] = json_encode($value);
    }

    public function getRatingsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setDepartmentsAttribute($value)
    {
        $this->attributes['departments'] = json_encode($value);
    }

    public function getDepartmentsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function addRating($type, $rating)
    {
        $max = config('review-ratable.max_rating_value');
        $min = config('review-ratable.min_rating_value');

        if ($rating >= $min && $rating <= $max) {
            $ratings = $this->ratings;
            $ratings[$type] = $rating;
            $this->ratings = $ratings;
            $this->save();
        }
    }

    public function getRating($type)
    {
        $ratings = $this->ratings;
        return $ratings[$type] ?? null;
    }
}
