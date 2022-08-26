<?php

namespace Codebyray\ReviewRateable\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ReviewRateable
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function ratings();

    /**
     *
     * @param $round
     * @return double
     */
    public function averageRating($round = null);

    /**
     *
     * @param $round
     * @return double
     */
    public function averageCustomerServiceRating($round = null);

    /**
     *
     * @param $round
     * @return double
     */
    public function averageQualityRating($round = null);

    /**
     *
     * @param $round
     * @return double
     */
    public function averageFriendlyRating($round = null);

    /**
     *
     * @param $round
     * @return double
     */
    public function averagePricingRating($round = null);

    /**
     *
     * @return int
     */
    public function countRating();

    /**
     *
     * @return int
     */
    public function countCustomerServiceRating();

    /**
     *
     * @return int
     */
    public function countQualityRating();

    /**
     *
     * @return int
     */
    public function countFriendlyRating();

    /**
     *
     * @return int
     */
    public function countPriceRating();

    /**
     *
     * @return double
     */
    public function sumRating();

    /**
     *
     * @param $max
     *
     * @return double
     */
    public function ratingPercent($max = 5);

    /**
     *
     * @param $data
     * @param $author
     * @param $parent
     *
     * @return static
     */
    public function rating($data, Model $author, Model $parent = null);

    /**
     *
     * @param $id
     * @param $data
     * @param $parent
     *
     * @return mixed
     */
    public function updateRating($id, $data, Model $parent = null);

    /**
     *
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getAllRatings($id, $sort = 'desc');

    /**
     *
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getApprovedRatings($id, $sort = 'desc');

    /**
     *
     * @param $id
     * @param $sort
     *
     * @return mixed
     */
    public function getNotApprovedRatings($id, $sort = 'desc');

    /**
     * @param $id
     * @param $limit
     * @param $sort
     *
     * @return mixed
     */
    public function getRecentRatings($id, $limit = 5, $sort = 'desc');

    /**
     * @param $id
     * @param $limit
     * @param $approved
     * @param $sort
     *
     * @return mixed
     */
    public function getRecentUserRatings($id, $limit = 5, $approved = true, $sort = 'desc');

    /**
     * @param $rating
     * @para $type
     * @param $approved
     * @param $sort
     *
     * @return mixed
     */
    public function getCollectionByAverageRating($rating, $type = 'rating', $approved = true, $sort = 'desc');

    /**
     *
     * @param $id
     *
     * @return mixed
     */
    public function deleteRating($id);

    /**
     *
     * @param $id
     * @return mixed
     */
    public function getUserRatings($id, $author, $sort = 'desc');
}
