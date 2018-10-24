<?php

namespace CodebyRay\ReviewRateable\Contracts;

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
     * @return mixed
     */
    public function deleteRating($id);
}
