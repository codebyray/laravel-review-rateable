<?php

namespace Codebyray\ReviewRateable\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ReviewRateableContract
{
    /**
     * Set the model instance to operate on.
     *
     * @param  mixed  $model  A model instance using the ReviewRateable trait.
     * @return self
     */
    public function setModel(mixed $model): self;

    /**
     * Add a review to the attached model.
     *
     * @param  array     $data   Review data (e.g., 'review', 'department', 'recommend', 'ratings').
     * @param  int|null  $userId Optional user ID.
     * @return mixed
     */
    public function addReview(array $data, ?int $userId = null): mixed;

    /**
     * Update a review by its ID.
     *
     * @param  int|null   $reviewId
     * @param  array|null $data
     * @return bool
     */
    public function updateReview(?int $reviewId = null, ?array $data = null): bool;

    /**
     * Mark a review as approved by its ID.
     *
     * @param  int|null $reviewId
     * @return bool
     */
    public function approveReview(?int $reviewId = null): bool;

    /**
     * Get the average rating for a given key.
     *
     * @param  string|null $key
     * @param  bool        $approved
     * @return float|null
     */
    public function averageRating(?string $key = null, bool $approved = true): ?float;

    /**
     * Get overall average ratings for all keys.
     *
     * @param  bool $approved
     * @return array
     */
    public function averageRatings(bool $approved = true): array;

    /**
     * Get the average rating for a given key within a department.
     *
     * @param  string      $department
     * @param  string|null $key
     * @param  bool        $approved
     * @return float|null
     */
    public function averageRatingByDepartment(
        string $department = "default",
        ?string $key = null,
        bool $approved = true
    ): ?float;

    /**
     * Get overall average ratings for all keys within a department.
     *
     * @param  string $department
     * @param  bool   $approved
     * @return array
     */
    public function averageRatingsByDepartment(string $department = "default", bool $approved = true): array;

    /**
     * Get all reviews (with attached ratings) for the attached model.
     *
     * @param  bool $approved
     * @param  bool $withRatings
     * @return Collection
     */
    public function getReviews(bool $approved = true, bool $withRatings = true): Collection;

    /**
     * Get all reviews (with attached ratings) for a department,
     * filtered by the approved status.
     *
     * @param  string $department
     * @param  bool   $approved
     * @param  bool   $withRatings
     * @return Collection
     */
    public function getReviewsByDepartment(
        string $department = "default",
        bool $approved = true,
        bool $withRatings = true
    ): Collection;

    /**
     * Get the total number of reviews for the attached model.
     *
     * @param  bool $approved
     * @return int
     */
    public function totalReviews(bool $approved = true): int;

    /**
     * Get the total number of reviews for the model by department.
     *
     * @param  string $department
     * @param  bool   $approved
     * @return int
     */
    public function totalDepartmentReviews(string $department = "default", bool $approved = true): int;

    /**
     * Get the overall average rating for all ratings attached to the model.
     *
     * @param  bool $approved
     * @return float|null
     */
    public function overallAverageRating(bool $approved = true): ?float;

    /**
     * Delete a review.
     *
     * @param  int|null $reviewId
     * @return bool
     */
    public function deleteReview(?int $reviewId = null): bool;

    /**
     * Return an array of rating value ⇒ count, for the full model
     * or for a given department.
     *
     * @param  string|null $department
     * @param  bool        $approved
     * @return array
     */
    public function ratingCounts(?string $department = "default", bool $approved = true): array;

    /**
     * Return an array with:
     *  • counts:     [1 => x, 2 => y, …, 5 => z]
     *  • percentages: [1 => pct1, …, 5 => pct5]
     *  • total:      total number of ratings
     *
     * @param  string|null $department
     * @param  bool        $approved
     * @return array
     */
    public function ratingStats(?string $department = "default", bool $approved = true): array;

    /**
     * Return reviews based on star ratings.
     *
     * @param  int|null    $starValue
     * @param  string      $department
     * @param  bool        $approved
     * @param  bool        $withRatings
     * @return Collection
     */
    public function getReviewsByRating(
        ?int $starValue = null,
        string $department = "default",
        bool $approved = true,
        bool $withRatings = true
    ): Collection;
}
