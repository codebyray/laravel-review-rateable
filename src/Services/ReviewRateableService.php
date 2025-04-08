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
    protected mixed $model;

    /**
     * Set the model instance to operate on.
     *
     * @param mixed $model
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
        if (!$this->model) {
            throw new Exception("No model set in ReviewRateableService. Please call setModel() first.");
        }
        return $this->model;
    }

    /**
     * Delegate adding a review to the model.
     *
     * @param array $data
     * @param int|null $userId
     * @return ReviewRateableContract
     * @throws Exception
     */
    public function addReview(array $data, ?int $userId = null): ReviewRateableContract
    {
        return $this->getModel()->addReview($data, $userId);
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
     * @param int $reviewId
     * @param array $data
     * @return bool  True on success, false if the review was not found.
     * @throws Exception
     */
    public function updateReview(int $reviewId, array $data): bool
    {
        return $this->getModel()->updateReview($reviewId, $data);
    }

    /**
     * Delegate approving a review.
     */
    public function approveReview(int $reviewId): bool
    {
        return $this->getModel()->approveReview($reviewId);
    }

    /**
     * Delegate averageRating calculation to the model.
     *
     * @param string $key
     * @param bool $approved
     * @return float|null
     * @throws Exception
     */
    public function averageRating(string $key, bool $approved = true): ?float
    {
        return $this->getModel()->averageRating($key, $approved);
    }

    /**
     * Delegate averageRatings calculation to the model.
     *
     * @param bool $approved
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
     * @param string $department
     * @param string $key
     * @param bool $approved
     * @return float|null
     * @throws Exception
     */
    public function averageRatingByDepartment(string $department, string $key, bool $approved = true): ?float
    {
        return $this->getModel()->averageRatingByDepartment($department, $key, $approved);
    }

    /**
     * Delegate averageRatingsByDepartment calculation to the model.
     *
     * @param string $department
     * @param bool $approved
     * @return array
     * @throws Exception
     */
    public function averageRatingsByDepartment(string $department, bool $approved = true): array
    {
        return $this->getModel()->averageRatingsByDepartment($department, $approved);
    }

    /**
     * Get all reviews (with attached ratings) for the attached model.
     *
     * @param bool $approved
     * @param bool $withRatings
     * @return Collection
     * @throws Exception
     */
    public function getReviews(bool $approved = true, bool $withRatings = true): Collection
    {
        return $this->getModel()->getReviews($approved, $withRatings);
    }

    /**
     * Get the total number of ratings for the attached model.
     *
     * @param bool $approved
     * @return int
     * @throws Exception
     */
    public function totalReviews(bool $approved = true): int
    {
        return $this->getModel()->reviewCount($approved);
    }

    /**
     * Get the overall average rating for all ratings attached to the model.
     *
     * @param bool $approved
     * @return float|null
     * @throws Exception
     */
    public function overallAverageRating(bool $approved = true): ?float
    {
        return $this->getModel()->overallAverageRating($approved);
    }

    /**
     * @param int $reviewId
     * @return bool
     * @throws Exception
     */
    public function deleteReview(int $reviewId): bool
    {
        return $this->getModel()->deleteReview($reviewId);
    }

}
