<?php

namespace Codebyray\ReviewRateable\Traits;

use Codebyray\ReviewRateable\Models\Rating;
use Codebyray\ReviewRateable\Models\Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait ReviewRateable
{
    /**
     * Get all reviews for the model.
     */
    public function reviews(): MorphMany
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
     */
    public function addReview(array $data, ?int $userId = null): Review
    {
        // Determine department, recommendation, and approval status.
        $department = $data['department'] ?? 'default';
        $recommend = $data['recommend'] ?? false;
        $approved = $data['approved'] ?? config('review-rateable.approved_review', false);

        // Create the review record.
        $review = $this->reviews()->create(
            [
                'user_id' => $userId,
                'review' => $data['review'] ?? null,
                'department' => $department,
                'recommend' => $recommend,
                'approved' => $approved,
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
                        'key' => $key,
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
     * @return bool True on success, false if the review was not found or invalid input.
     */
    public function updateReview(?int $reviewId = null, ?array $data = null): bool
    {
        if ($reviewId === null || $data === null) {
            return false;
        }

        $review = $this->reviews()->find($reviewId);

        if (! $review) {
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
        if (! empty($attributes)) {
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
     * @return bool True if the update was successful, false if the review was not found.
     */
    public function approveReview(?int $reviewId = null): bool
    {
        if ($reviewId === null) {
            return false;
        }

        $review = $this->reviews()->find($reviewId);
        if (! $review) {
            return false;
        }

        return $review->update(['approved' => true]);
    }

    /**
     * Calculate the average rating for a given key, filtering reviews by approval.
     */
    public function averageRating(?string $key = null, bool $approved = true): ?float
    {
        return Rating::where('key', $key)
            ->whereIn(
                'review_id',
                $this->reviews()->where('approved', $approved)->select('id')
            )->avg('value');
    }

    /**
     * Get overall average ratings for all keys, filtering reviews by approval.
     *
     * @return array Format: ['overall' => 4.5, 'quality' => 4.0, ...]
     */
    public function averageRatings(bool $approved = true): array
    {
        return Rating::selectRaw('key, AVG(value) as average')
            ->whereIn(
                'review_id',
                $this->reviews()->where('approved', $approved)->select('id')
            )
            ->groupBy('key')
            ->pluck('average', 'key')
            ->toArray();
    }

    /**
     * Calculate the average rating for a given key within a department,
     * filtering reviews by approval.
     */
    public function averageRatingByDepartment(
        string $department = 'default',
        ?string $key = null,
        bool $approved = true
    ): ?float {
        return Rating::where('key', $key)
            ->whereIn(
                'review_id',
                $this->reviews()
                    ->where('department', $department)
                    ->where('approved', $approved)
                    ->select('id')
            )->avg('value');
    }

    /**
     * Get overall average ratings for all keys within a department,
     * filtering reviews by approval.
     *
     * @return array Format: ['overall' => 4.5, 'quality' => 4.0, ...]
     */
    public function averageRatingsByDepartment(string $department = 'default', bool $approved = true): array
    {
        return Rating::selectRaw('key, AVG(value) as average')
            ->whereIn(
                'review_id',
                $this->reviews()
                    ->where('department', $department)
                    ->where('approved', $approved)
                    ->select('id')
            )
            ->groupBy('key')
            ->pluck('average', 'key')
            ->toArray();
    }

    /**
     * Get all reviews (with attached ratings) for the model,
     * filtered by the approved status.
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
     * Get all reviews (with attached ratings) for a department,
     * filtered by the approved status.
     */
    public function getReviewsByDepartment(
        string $department = 'default',
        bool $approved = true,
        bool $withRatings = true
    ): Collection {
        $query = $this->reviews()
            ->where('department', $department)
            ->where('approved', $approved);

        if ($withRatings) {
            $query->with('ratings');
        }

        return $query->get();
    }

    /**
     * Get the total number of reviews for the model.
     */
    public function totalReviews(bool $approved = true): int
    {
        return $this->reviews()->where('approved', $approved)->count();
    }

    /**
     * Get the total number of reviews for the model by department.
     */
    public function totalDepartmentReviews(string $department = 'default', bool $approved = true): int
    {
        return $this->reviews()
            ->where('department', $department)
            ->where('approved', $approved)->count();
    }

    /**
     * Calculate the overall average rating for all ratings across all reviews,
     * optionally filtering by the approved status.
     */
    public function overallAverageRating(bool $approved = true): ?float
    {
        return Rating::whereIn(
            'review_id',
            $this->reviews()->where('approved', $approved)->select('id')
        )->avg('value');
    }

    /**
     * Delete a review by its ID.
     *
     * @return bool True if the review was deleted, false otherwise.
     */
    public function deleteReview(?int $reviewId = null): bool
    {
        if ($reviewId === null) {
            return false;
        }

        $review = $this->reviews()->find($reviewId);

        if ($review) {
            return $review->delete();
        }

        return false;
    }

    /**
     * Return an array of rating value ⇒ count, for the full model
     * or for a given department.
     *
     * @param  string|null  $department  If null, counts across all departments.
     * @param  bool  $approved  Only count approved reviews?
     * @return array [1 => 12, 2 => 5, 3 => 23, 4 => 17, 5 => 42]
     */
    public function ratingCounts(?string $department = 'default', bool $approved = true): array
    {
        $min = config('review-rateable.min_rating_value', 1);
        $max = config('review-rateable.max_rating_value', 5);
        $reviewTable = (new Review)->getTable();
        $ratingTable = (new Rating)->getTable();

        $query = Rating::select("{$ratingTable}.value", DB::raw('COUNT(*) as total'))
            ->join($reviewTable, "{$ratingTable}.review_id", '=', "{$reviewTable}.id")
            ->where("{$reviewTable}.reviewable_type", $this->getMorphClass())
            ->where("{$reviewTable}.reviewable_id", $this->getKey())
            ->where("{$reviewTable}.approved", $approved);

        if ($department) {
            $query->where("{$reviewTable}.department", $department);
        }

        $raw = $query
            ->groupBy("{$ratingTable}.value")
            ->pluck('total', 'value')
            ->all();

        // zero-fill any missing star values
        $counts = [];
        for ($i = $min; $i <= $max; $i++) {
            $counts[$i] = $raw[$i] ?? 0;
        }

        return $counts;
    }

    /**
     * Return an array with:
     *  • counts: [1 => x, 2 => y, …, 5 => z]
     *  • percentages: [1 => pct1, …, 5 => pct5]
     *  • total: total number of ratings
     */
    public function ratingStats(?string $department = 'default', bool $approved = true): array
    {
        $min = config('review-rateable.min_rating_value', 1);
        $max = config('review-rateable.max_rating_value', 5);
        $reviewTable = (new Review)->getTable();
        $ratingTable = (new Rating)->getTable();

        // base query: gives you value => count
        $raw = Rating::select("{$ratingTable}.value", DB::raw('COUNT(*) as count'))
            ->join($reviewTable, "{$ratingTable}.review_id", '=', "{$reviewTable}.id")
            ->where("{$reviewTable}.reviewable_type", $this->getMorphClass())
            ->where("{$reviewTable}.reviewable_id", $this->getKey())
            ->where("{$reviewTable}.approved", $approved)
            ->when($department, fn ($q) => $q->where("{$reviewTable}.department", $department))
            ->groupBy("{$ratingTable}.value")
            ->pluck('count', 'value')
            ->all();

        // zero-fill missing star values
        $counts = [];
        for ($i = $min; $i <= $max; $i++) {
            $counts[$i] = $raw[$i] ?? 0;
        }

        // total number of ratings
        $total = array_sum($counts);

        // percentages (integer 0–100)
        $percentages = [];
        foreach ($counts as $star => $count) {
            $percentages[$star] = $total
                ? round(($count / $total) * 100)
                : 0;
        }

        return [
            'counts' => $counts,
            'percentages' => $percentages,
            'total' => $total,
        ];
    }

    /**
     * Return reviews based on star ratings.
     */
    public function getReviewsByRating(
        ?int $starValue = null,
        string $department = 'default',
        bool $approved = true,
        bool $withRatings = true
    ): Collection {
        $query = $this->reviews()
            ->when($approved, fn ($q) => $q->where('approved', $approved))
            ->when($department, fn ($q) => $q->where('department', $department))
            ->whereHas(
                'ratings', fn ($q) => $q->where('value', $starValue)
            );

        if ($withRatings) {
            $query->with('ratings');
        }

        return $query->get();
    }
}
