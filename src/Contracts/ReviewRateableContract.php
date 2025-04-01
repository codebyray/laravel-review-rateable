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
     * Get the average rating for a given key.
     *
     * @param string $key
     * @param bool $approved
     * @return float|null
     */
    public function averageRating(string $key, bool $approved = true): ?float;

    /**
     * Get overall average ratings for all keys.
     *
     * @param bool $approved
     * @return array
     */
    public function averageRatings(bool $approved = true): array;

    /**
     * Get the average rating for a given key within a department.
     *
     * @param string $department
     * @param string $key
     * @param bool $approved
     * @return float|null
     */
    public function averageRatingByDepartment(string $department, string $key, bool $approved = true): ?float;

    /**
     * Get overall average ratings for all keys within a department.
     *
     * @param string $department
     * @param bool $approved
     * @return array
     */
    public function averageRatingsByDepartment(string $department, bool $approved = true): array;

    /**
     * Get all reviews (with attached ratings) for the attached model.
     *
     * @param bool $approved
     * @param bool $withRatings
     * @return Collection
     */
    public function getReviews(bool $approved = true, bool $withRatings = true): Collection;

    /**
     * Get the total number of reviews for the attached model.
     *
     * @param bool $approved
     * @return int
     */
    public function totalReviews(bool $approved = true): int;

    /**
     * Get the overall average rating for all ratings attached to the model.
     *
     * @param bool $approved
     * @return float|null
     */
    public function overallAverageRating(bool $approved = true): ?float;
}
