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
     * @return mixed
     */
    public function averageRating($round = null);

    /**
     *
     * @param $round
     * @return mixed
     */
    public function averageCustomerServiceRating($round = null);

    /**
     *
     * @param $round
     * @return mixed
     */
    public function averageQualityRating($round = null);

    /**
     *
     * @param $round
     * @return mixed
     */
    public function averageFriendlyRating($round = null);

    /**
     *
     * @param $round
     * @return mixed
     */
    public function averagePricingRating($round = null);

    /**
     *
     * @return mixed
     */
    public function countRating();

    /**
     *
     * @return mixed
     */
    public function countCustomerServiceRating();

    /**
     *
     * @return mixed
     */
    public function countQualityRating();

    /**
     *
     * @return mixed
     */
    public function countFriendlyRating();

    /**
     *
     * @return mixed
     */
    public function countPriceRating();

    /**
     *
     * @return mixed
     */
    public function sumRating();

    /**
     *
     * @param $max
     * @return mixed
     */
    public function ratingPercent($max = 5);

    /**
     *
     * @param $data
     * @param $author
     * @param $parent
     * @return mixed
     */
    public function rating($data, Model $author, Model $parent = null);

    /**
     *
     * @param $id
     * @param $data
     * @param $parent
     * @return mixed
     */
    public function updateRating($id, $data, Model $parent = null);

    /**
     *
     * @param $id
     * @param $sort
     * @return mixed
     */
    public function getAllRatings($id, $sort = 'desc');

    /**
     *
     * @param $id
     * @param $sort
     * @return mixed
     */
    public function getApprovedRatings($id, $sort = 'desc');

    /**
     *
     * @param $id
     * @param $sort
     * @return mixed
     */
    public function getNotApprovedRatings($id, $sort = 'desc');

    /**
     * @param $id
     * @param $limit
     * @param $sort
     * @return mixed
     */
    public function getRecentRatings($id, $limit = 5, $sort = 'desc');

    /**
     * @param $id
     * @param $limit
     * @param $approved
     * @param $sort
     * @return mixed
     */
    public function getRecentUserRatings($id, $limit = 5, $approved = true, $sort = 'desc');

    /**
     *
     * @param $id
     * @return mixed
     */
    public function deleteRating($id);
}
