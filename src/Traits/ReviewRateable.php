<?php

namespace Codebyray\ReviewRateable\Traits;

use Codebyray\ReviewRateable\Models\Review;
use Illuminate\Database\Eloquent\Collection;

trait ReviewRateable
{
    /**
     * Get all reviews for the model.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Add a review along with its ratings.
     *
     * The $data array can include:
     *  - 'review': The review text.
     *  - 'department': The department key (defaults to 'default').
     *  - 'recommend': Boolean indicating if the reviewer recommends the item.
     *  - 'approved': (Optional) Whether the review is approved. If not provided, uses config value.
     *  - 'ratings': An associative array of rating values.
     *
     * @param  array      $data
     * @param int|null $userId
     * @return Review
     */
    public function addReview(array $data, ?int $userId = null): Review
    {
        // Determine department, recommendation, and approval status.
        $department = $data['department'] ?? 'default';
        $recommend  = $data['recommend'] ?? false;
        $approved   = $data['approved'] ?? config('review-rateable.approved_review', false);

        // Create the review record.
        $review = $this->reviews()->create(
            [
                'user_id'    => $userId,
                'review'     => $data['review'] ?? null,
                'department' => $department,
                'recommend'  => $recommend,
                'approved'   => $approved,
            ]
        );

        // Get allowed rating keys for the specified department.
        $departments = config('review-rateable.departments', []);
        $configRatings = $departments[$department]['ratings'] ?? [];

        // Get global min and max rating values.
        $min = config('review-rateable.min_rating_value', 1);
        $max = config('review-rateable.max_rating_value', 10);

        // Process each allowed rating key.
        foreach ($configRatings as $key => $label) {
            if (isset($data['ratings'][$key])) {
                $value = $data['ratings'][$key];

                // Enforce rating boundaries.
                if ($value < $min) {
                    $value = $min;
                } elseif ($value > $max) {
                    $value = $max;
                }

                $review->ratings()->create(
                    [
                        'key'   => $key,
                        'value' => $value,
                    ]
                );
            }
        }

        return $review;
    }

    /**
     * Update a review and its ratings by review ID.
     *
     * The $data array can include:
     *  - 'review': New review text.
     *  - 'department': New department key.
     *  - 'recommend': New recommendation flag.
     *  - 'approved': New approval status.
     *  - 'ratings': An associative array of rating values (key => value).
     *
     * @param int   $reviewId
     * @param array $data
     * @return bool  True on success, false if the review was not found.
     */
    public function updateReview(int $reviewId, array $data): bool
    {
        $review = $this->reviews()->find($reviewId);

        if (!$review) {
            return false;
        }

        // Prepare attributes for the review update.
        $attributes = [];
        if (isset($data['review'])) {
            $attributes['review'] = $data['review'];
        }
        if (isset($data['department'])) {
            $attributes['department'] = $data['department'];
        }
        if (isset($data['recommend'])) {
            $attributes['recommend'] = $data['recommend'];
        }
        if (isset($data['approved'])) {
            $attributes['approved'] = $data['approved'];
        }
        if (!empty($attributes)) {
            $review->update($attributes);
        }

        // Update ratings if provided.
        if (isset($data['ratings']) && is_array($data['ratings'])) {
            // Determine which department's rating keys to use.
            $department = $attributes['department'] ?? $review->department;
            $departments = config('review-rateable.departments', []);
            $configRatings = $departments[$department]['ratings'] ?? [];

            // Get global min and max rating values.
            $min = config('review-rateable.min_rating_value', 1);
            $max = config('review-rateable.max_rating_value', 10);

            foreach ($data['ratings'] as $key => $value) {
                if ($value < $min) {
                    $value = $min;
                } elseif ($value > $max) {
                    $value = $max;
                }

                $rating = $review->ratings()->where('key', $key)->first();
                if ($rating) {
                    $rating->update(['value' => $value]);
                } else {
                    if (array_key_exists($key, $configRatings)) {
                        $review->ratings()->create(['key' => $key, 'value' => $value]);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Mark a review as approved by its ID.
     *
     * @param int $reviewId
     * @return bool True if the update was successful, false if the review was not found.
     */
    public function approveReview(int $reviewId): bool
    {
        $review = $this->reviews()->find($reviewId);
        if (!$review) {
            return false;
        }
        return $review->update(['approved' => true]);
    }

    /**
     * Get all reviews (with attached ratings) for the model,
     * filtered by the approved status.
     *
     * @param bool $approved
     * @param bool $withRatings
     * @return Collection
     */
    public function getReviews(bool $approved = true, bool $withRatings = true): Collection
    {
        $query = $this->reviews()->where('approved', $approved);

        if ($withRatings) {
            $query->with('ratings');
        }

        return $query->get();
    }

    /**
     * Calculate the average rating for a given key, filtering reviews by approval.
     *
     * @param string $key
     * @param bool $approved
     * @return float|null
     */
    public function averageRating(string $key, bool $approved = true): ?float
    {
        return $this->reviews()
            ->where('approved', $approved)
            ->whereHas('ratings', function ($query) use ($key) {
                $query->where('key', $key);
            })
            ->with('ratings')
            ->get()
            ->pluck('ratings')
            ->flatten()
            ->where('key', $key)
            ->avg('value');
    }

    /**
     * Get overall average ratings for all keys, filtering reviews by approval.
     *
     * @param bool $approved
     * @return array  Format: ['overall' => 4.5, 'quality' => 4.0, ...]
     */
    public function averageRatings(bool $approved = true): array
    {
        $averages = [];

        $this->reviews()
            ->where('approved', $approved)
            ->with('ratings')
            ->get()
            ->each(function ($review) use (&$averages) {
                foreach ($review->ratings as $rating) {
                    if (!isset($averages[$rating->key])) {
                        $averages[$rating->key] = ['sum' => 0, 'count' => 0];
                    }
                    $averages[$rating->key]['sum'] += $rating->value;
                    $averages[$rating->key]['count']++;
                }
            });

        foreach ($averages as $key => $data) {
            $averages[$key] = $data['count'] ? $data['sum'] / $data['count'] : null;
        }

        return $averages;
    }

    /**
     * Calculate the average rating for a given key within a department,
     * filtering reviews by approval.
     *
     * @param string $department
     * @param string $key
     * @param bool $approved
     * @return float|null
     */
    public function averageRatingByDepartment(string $department, string $key, bool $approved = true): ?float
    {
        return $this->reviews()
            ->where('department', $department)
            ->where('approved', $approved)
            ->whereHas('ratings', function ($query) use ($key) {
                $query->where('key', $key);
            })
            ->with('ratings')
            ->get()
            ->pluck('ratings')
            ->flatten()
            ->where('key', $key)
            ->avg('value');
    }

    /**
     * Get overall average ratings for all keys within a department,
     * filtering reviews by approval.
     *
     * @param string $department
     * @param bool $approved
     * @return array  Format: ['overall' => 4.5, 'quality' => 4.0, ...]
     */
    public function averageRatingsByDepartment(string $department, bool $approved = true): array
    {
        $averages = [];

        $this->reviews()
            ->where('department', $department)
            ->where('approved', $approved)
            ->with('ratings')
            ->get()
            ->each(function ($review) use (&$averages) {
                foreach ($review->ratings as $rating) {
                    if (!isset($averages[$rating->key])) {
                        $averages[$rating->key] = ['sum' => 0, 'count' => 0];
                    }
                    $averages[$rating->key]['sum'] += $rating->value;
                    $averages[$rating->key]['count']++;
                }
            });

        foreach ($averages as $key => $data) {
            $averages[$key] = $data['count'] ? $data['sum'] / $data['count'] : null;
        }

        return $averages;
    }

    /**
     * Get the total number of reviews for the model.
     *
     * @param bool $approved
     * @return int
     */
    public function totalReviews(bool $approved = true): int
    {
        return $this->reviews()->where('approved', $approved)->count();
    }

    /**
     * Calculate the overall average rating for all ratings across all reviews,
     * optionally filtering by the approved status.
     *
     * @param bool $approved
     * @return float|null
     */
    public function overallAverageRating(bool $approved = true): ?float
    {
        $ratings = $this->reviews()
            ->where('approved', $approved)
            ->with('ratings')
            ->get()
            ->pluck('ratings')
            ->flatten();

        return $ratings->avg('value');
    }

    /**
     * Delete a review by its ID.
     *
     * @param int $reviewId
     * @return bool True if the review was deleted, false otherwise.
     */
    public function deleteReview(int $reviewId): bool
    {
        $review = $this->reviews()->find($reviewId);

        if ($review) {
            return $review->delete();
        }

        return false;
    }

}
