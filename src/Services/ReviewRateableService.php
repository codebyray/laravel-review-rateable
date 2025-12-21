<?php

namespace Codebyray\ReviewRateable\Services;

use Codebyray\ReviewRateable\Contracts\ReviewRateableContract;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class ReviewRateableService implements ReviewRateableContract
{
    /**
     * The reviewable model instance.
     */
    protected mixed $model = null;

    /**
     * Set the model instance to operate on.
     *
     * @param  mixed $model
     * @return self
     */
    public function setModel(mixed $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Retrieve the model instance or throw an exception.
     *
     * @return mixed
     * @throws Exception
     */
    protected function getModel(): mixed
    {
        if ($this->model === null) {
            throw new Exception("No model set in ReviewRateableService. Please call setModel() first.");
        }

        return $this->model;
    }

    /**
     * Delegate adding a review to the model.
     *
     * @param  array     $data
     * @param  int|null  $userId
     * @return ReviewRateableContract
     * @throws Exception
     */
    public function addReview(array $data, ?int $userId = null): mixed
    {
        return $this->getModel()->addReview($data, $userId);
    }

    /**
     * Update a review and its ratings by review ID.
     *
     * @param  int|null   $reviewId
     * @param  array|null $data
     * @return bool  True on success, false if the review was not found.
     * @throws Exception
     */
    public function updateReview(?int $reviewId = null, ?array $data = null): bool
    {
        return $this->getModel()->updateReview($reviewId, $data);
    }

    /**
     * Delegate approving a review.
     *
     * @param  int|null $reviewId
     * @return bool
     * @throws Exception
     */
    public function approveReview(?int $reviewId = null): bool
    {
        return $this->getModel()->approveReview($reviewId);
    }

    /**
     * Delegate averageRating calculation to the model.
     *
     * @param  string|null $key
     * @param  bool        $approved
     * @return float|null
     * @throws Exception
     */
    public function averageRating(?string $key = null, bool $approved = true): ?float
    {
        return $this->getModel()->averageRating($key, $approved);
    }

    /**
     * Delegate averageRatings calculation to the model.
     *
     * @param  bool $approved
     * @return array
     * @throws Exception
     */
    public function averageRatings(bool $approved = true): array
    {
        return $this->getModel()->averageRatings($approved);
    }

    /**
     * Delegate averageRatingByDepartment calculation to the model.
     *
     * @param  string      $department
     * @param  string|null $key
     * @param  bool        $approved
     * @return float|null
     * @throws Exception
     */
    public function averageRatingByDepartment(
        string $department = "default",
        ?string $key = null,
        bool $approved = true
    ): ?float {
        return $this->getModel()->averageRatingByDepartment($department, $key, $approved);
    }

    /**
     * Delegate averageRatingsByDepartment calculation to the model.
     *
     * @param  string $department
     * @param  bool   $approved
     * @return array
     * @throws Exception
     */
    public function averageRatingsByDepartment(string $department = "default", bool $approved = true): array
    {
        return $this->getModel()->averageRatingsByDepartment($department, $approved);
    }

    /**
     * Get all reviews (with attached ratings) for the attached model.
     *
     * @param  bool $approved
     * @param  bool $withRatings
     * @return Collection
     * @throws Exception
     */
    public function getReviews(bool $approved = true, bool $withRatings = true): Collection
    {
        return $this->getModel()->getReviews($approved, $withRatings);
    }

    /**
     * Get all reviews (with attached ratings) for a department,
     * filtered by the approved status.
     *
     * @param  string $department
     * @param  bool   $approved
     * @param  bool   $withRatings
     * @return Collection
     * @throws Exception
     */
    public function getReviewsByDepartment(
        string $department = "default",
        bool $approved = true,
        bool $withRatings = true
    ): Collection {
        return $this->getModel()->getReviewsByDepartment($department, $approved, $withRatings);
    }

    /**
     * Get the total number of ratings for the attached model.
     *
     * @param  bool $approved
     * @return int
     * @throws Exception
     */
    public function totalReviews(bool $approved = true): int
    {
        return $this->getModel()->reviewCount($approved);
    }

    /**
     * Get the total number of reviews for the model by department.
     *
     * @param  string $department
     * @param  bool   $approved
     * @return int
     * @throws Exception
     */
    public function totalDepartmentReviews(string $department = "default", bool $approved = true): int
    {
        return $this->getModel()->totalReviews($department, $approved);
    }

    /**
     * Get the overall average rating for all ratings attached to the model.
     *
     * @param  bool $approved
     * @return float|null
     * @throws Exception
     */
    public function overallAverageRating(bool $approved = true): ?float
    {
        return $this->getModel()->overallAverageRating($approved);
    }

    /**
     * Delete a review.
     *
     * @param  int|null $reviewId
     * @return bool
     * @throws Exception
     */
    public function deleteReview(?int $reviewId = null): bool
    {
        return $this->getModel()->deleteReview($reviewId);
    }

    /**
     * Return an array of rating value ⇒ count, for the full model
     * or for a given department.
     *
     * @param  string|null $department
     * @param  bool        $approved
     * @return array
     * @throws Exception
     */
    public function ratingCounts(?string $department = "default", bool $approved = true): array
    {
        return $this->getModel()->ratingCounts($department, $approved);
    }

    /**
     * Return an array with:
     *  • counts:     [1 => x, 2 => y, …, 5 => z]
     *  • percentages: [1 => pct1, …, 5 => pct5]
     *  • total:      total number of ratings
     *
     * @param  string|null $department
     * @param  bool        $approved
     * @return array
     * @throws Exception
     */
    public function ratingStats(?string $department = "default", bool $approved = true): array
    {
        return $this->getModel()->ratingStats($department, $approved);
    }

    /**
     * Return reviews based on star ratings.
     *
     * @param  int|null    $starValue
     * @param  string      $department
     * @param  bool        $approved
     * @param  bool        $withRatings
     * @return Collection
     * @throws Exception
     */
    public function getReviewsByRating(
        ?int $starValue = null,
        string $department = "default",
        bool $approved = true,
        bool $withRatings = true
    ): Collection {
        return $this->getModel()->getReviewsByRating($starValue, $department, $approved, $withRatings);
    }
}
