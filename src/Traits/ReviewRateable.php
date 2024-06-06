<?php

namespace Codebyray\ReviewRateable\Traits;

use Codebyray\ReviewRateable\Models\Review;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ReviewRateable
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewrateable');
    }

    public function ratingTypes(): array
    {
        return config('review-ratable.default_rating_types');
    }

    public function addReview(array $data, Model $author)
    {
        // Separate ratings from other data
        $ratings = [];
        foreach ($this->ratingTypes() as $type) {
            if (isset($data["{$type}_rating"])) {
                $ratings[$type] = $data["{$type}_rating"];
                unset($data["{$type}_rating"]);
            }
        }
        $data['ratings'] = $ratings;

        // Create review
        $data['author_id'] = $author->id;
        $data['author_type'] = get_class($author);
        $data['approved'] = $data['approved'] ?? config('review-ratable.default_approved', false);
        $review = new Review($data);
        $this->reviews()->save($review);

        return $review;
    }

    public function addRatingToReview($reviewId, $type, $rating)
    {
        $review = $this->reviews()->find($reviewId);
        if ($review) {
            $review->addRating($type, $rating);
        }
    }

    public function getReviewRating($reviewId, $type)
    {
        $review = $this->reviews()->find($reviewId);
        if ($review) {
            return $review->getRating($type);
        }
        return null;
    }

    public function averageRating($type = 'rating', $round = null)
    {
        $reviews = $this->reviews()->whereNotNull("ratings->$type")->pluck("ratings->$type");
        $average = $reviews->avg();
        return is_null($round) ? $average : round($average, $round);
    }

    public function countRating($type = 'rating')
    {
        return $this->reviews()->whereNotNull("ratings->$type")->count();
    }

    public function sumRating($type = 'rating')
    {
        return $this->reviews()->whereNotNull("ratings->$type")->sum("ratings->$type");
    }

    public function ratingPercent($type = 'rating', $max = 5)
    {
        $total = $this->reviews()->count() * $max;
        $sum = $this->sumRating($type);
        return ($total > 0) ? ($sum / $total) * 100 : 0;
    }

    public function getAllRatings($id, $sort = 'desc')
    {
        return $this->reviews()->where('reviewrateable_id', $id)->orderBy('created_at', $sort)->get();
    }

    public function getApprovedRatings($id, $sort = 'desc')
    {
        return $this->reviews()->where('reviewrateable_id', $id)->where('approved', 1)->orderBy('created_at', $sort)->get();
    }

    public function getNotApprovedRatings($id, $sort = 'desc')
    {
        return $this->reviews()->where('reviewrateable_id', $id)->where('approved', 0)->orderBy('created_at', $sort)->get();
    }

    public function getRecentRatings($id, $limit = 5, $sort = 'desc')
    {
        return $this->reviews()->where('reviewrateable_id', $id)->orderBy('created_at', $sort)->limit($limit)->get();
    }

    public function getRecentUserRatings($id, $limit = 5, $approved = true, $sort = 'desc')
    {
        return $this->reviews()->where('author_id', $id)->where('approved', $approved)->orderBy('created_at', $sort)->limit($limit)->get();
    }

    public function getCollectionByAverageRating($rating, $type = 'rating', $approved = true, $sort = 'desc')
    {
        return $this->reviews()->where("ratings->$type", '>=', $rating)->where('approved', $approved)->orderBy("ratings->$type", $sort)->get();
    }

    public function deleteRating($id)
    {
        $review = $this->reviews()->find($id);
        if ($review) {
            return $review->delete();
        }
        return false;
    }

    public function getUserRatings($id, $author, $sort = 'desc')
    {
        return $this->reviews()->where('author_id', $author)->orderBy('created_at', $sort)->get();
    }
}
