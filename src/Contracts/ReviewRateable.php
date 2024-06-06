<?php

namespace Codebyray\ReviewRateable\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ReviewRateable
{
    /**
     * Get all reviews for the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function reviews();

    /**
     * Get the rating types for the model.
     *
     * @return array
     */
    public function ratingTypes();

    /**
     * Add a review to the model.
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function addReview(array $data);

    /**
     * Add a rating to a specific review.
     *
     * @param int $reviewId
     * @param string $type
     * @param int $rating
     * @return void
     */
    public function addRatingToReview($reviewId, $type, $rating);

    /**
     * Get a specific rating from a review.
     *
     * @param int $reviewId
     * @param string $type
     * @return int|null
     */
    public function getReviewRating($reviewId, $type);

    /**
     * Calculate the average rating for a specific type.
     *
     * @param string|null $type
     * @param int|null $round
     * @return double
     */
    public function averageRating($type = 'rating', $round = null);

    /**
     * Count the number of ratings for a specific type.
     *
     * @param string|null $type
     * @return int
     */
    public function countRating($type = 'rating');

    /**
     * Sum the ratings for a specific type.
     *
     * @param string|null $type
     * @return double
     */
    public function sumRating($type = 'rating');

    /**
     * Calculate the rating percentage for a specific type.
     *
     * @param string|null $type
     * @param int $max
     * @return double
     */
    public function ratingPercent($type = 'rating', $max = 5);

    /**
     * Retrieve all ratings for the given ID.
     *
     * @param int $id
     * @param string $sort
     * @return mixed
     */
    public function getAllRatings($id, $sort = 'desc');

    /**
     * Retrieve all approved ratings for the given ID.
     *
     * @param int $id
     * @param string $sort
     * @return mixed
     */
    public function getApprovedRatings($id, $sort = 'desc');

    /**
     * Retrieve all non-approved ratings for the given ID.
     *
     * @param int $id
     * @param string $sort
     * @return mixed
     */
    public function getNotApprovedRatings($id, $sort = 'desc');

    /**
     * Retrieve recent ratings for the given ID.
     *
     * @param int $id
     * @param int $limit
     * @param string $sort
     * @return mixed
     */
    public function getRecentRatings($id, $limit = 5, $sort = 'desc');

    /**
     * Retrieve recent user ratings.
     *
     * @param int $id
     * @param int $limit
     * @param bool $approved
     * @param string $sort
     * @return mixed
     */
    public function getRecentUserRatings($id, $limit = 5, $approved = true, $sort = 'desc');

    /**
     * Get a collection of reviews by average rating.
     *
     * @param double $rating
     * @param string $type
     * @param bool $approved
     * @param string $sort
     * @return mixed
     */
    public function getCollectionByAverageRating($rating, $type = 'rating', $approved = true, $sort = 'desc');

    /**
     * Delete a rating by its ID.
     *
     * @param int $id
     * @return mixed
     */
    public function deleteRating($id);

    /**
     * Retrieve user ratings.
     *
     * @param int $id
     * @param string $author
     * @param string $sort
     * @return mixed
     */
    public function getUserRatings($id, $author, $sort = 'desc');
}

