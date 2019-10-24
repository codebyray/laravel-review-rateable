<?php

namespace Codebyray\ReviewRateable\Traits;

use Illuminate\Database\Eloquent\Model;
use Codebyray\ReviewRateable\Models\Rating;

trait ReviewRateable
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function ratings()
    {
        return $this->morphMany(Rating::class, 'reviewrateable');
    }

    /**
     *
     * @var $round
     * @return mixed
     */
    public function averageRating($round= null)
    {
      if ($round) {
            return $this->ratings()
              ->selectRaw('ROUND(AVG(rating), '.$round.') as averageReviewRateable')
              ->pluck('averageReviewRateable');
        }

        return $this->ratings()
            ->selectRaw('AVG(rating) as averageReviewRateable')
            ->pluck('averageReviewRateable');
    }

    /**
     *
     * @var $round
     * @return mixed
     */
    public function averageCustomerServiceRating($round= null)
    {
        if ($round) {
            return $this->ratings()
                ->selectRaw('ROUND(AVG(customer_service_rating), '.$round.') as averageCustomerServiceReviewRateable')
                ->pluck('averageCustomerServiceReviewRateable');
        }

        return $this->ratings()
            ->selectRaw('AVG(customer_service_rating) as averageCustomerServiceReviewRateable')
            ->pluck('averageCustomerServiceReviewRateable');
    }

    /**
     *
     * @param $round
     * @return mixed
     */
    public function averageQualityRating($round = null)
    {
        if ($round) {
            return $this->ratings()
                ->selectRaw('ROUND(AVG(quality_rating), '.$round.') as averageQualityReviewRateable')
                ->pluck('averageQualityReviewRateable');
        }

        return $this->ratings()
            ->selectRaw('AVG(quality_rating) as averageQualityReviewRateable')
            ->pluck('averageQualityReviewRateable');
    }

    /**
     *
     * @var $round
     * @return mixed
     */
    public function averageFriendlyRating($round = null)
    {
        if ($round) {
            return $this->ratings()
                ->selectRaw('ROUND(AVG(friendly_rating), '.$round.') as averageFriendlyReviewRateable')
                ->pluck('averageFriendlyReviewRateable');
        }

        return $this->ratings()
            ->selectRaw('AVG(friendly_rating) as averageFriendlyReviewRateable')
            ->pluck('averageFriendlyReviewRateable');
    }

    /**
     *
     * @var $round
     * @return mixed
     */
    public function averagePricingRating($round = null)
    {
        if ($round) {
            return $this->ratings()
                ->selectRaw('ROUND(AVG(pricing_rating), '.$round.') as averagePricingReviewRateable')
                ->pluck('averagePricingReviewRateable');
        }

        return $this->ratings()
            ->selectRaw('AVG(pricing_rating) as averagePricingReviewRateable')
            ->pluck('averagePricingReviewRateable');
    }

    /**
     *
     * @return mixed
     */
    public function countRating()
    {
      return $this->ratings()
          ->selectRaw('count(rating) as countReviewRateable')
          ->pluck('countReviewRateable');
    }

    /**
     *
     * @return mixed
     */
    public function countCustomerServiceRating()
    {
        return $this->ratings()
            ->selectRaw('count(customer_service_rating) as countCustomerServiceReviewRateable')
            ->pluck('countCustomerServiceReviewRateable');
    }

    /**
     *
     * @return mixed
     */
    public function countQualityRating()
    {
        return $this->ratings()
            ->selectRaw('count(quality_rating) as countQualityReviewRateable')
            ->pluck('countQualityReviewRateable');
    }

    /**
     *
     * @return mixed
     */
    public function countFriendlyRating() {
        return $this->ratings()
            ->selectRaw('count(friendly_rating) as countFriendlyReviewRateable')
            ->pluck('countFriendlyReviewRateable');
    }

    /**
     *
     * @return mixed
     */
    public function countPriceRating() {
        return $this->ratings()
            ->selectRaw('count(price_rating) as countPriceReviewRateable')
            ->pluck('countPriceReviewRateable');
    }

    /**
     *
     * @return mixed
     */
    public function sumRating()
    {
        return $this->ratings()
            ->selectRaw('SUM(rating) as sumReviewRateable')
            ->pluck('sumReviewRateable');
    }

    /**
     * @param $max
     *
     * @return mixed
     */
    public function ratingPercent($max = 5)
    {
        $ratings = $this->ratings();
        $quantity = $ratings->count();
        $total = $ratings->selectRaw('SUM(rating) as total')->pluck('total');
        return ($quantity * $max) > 0 ? $total / (($quantity * $max) / 100) : 0;
    }

    /**
     * @param $data
     * @param $author
     * @param $parent
     *
     * @return mixed
     */
    public function rating($data, Model $author, Model $parent = null)
    {
        return (new Rating())->createRating($this, $data, $author);
    }

    /**
     * @param $id
     * @param $data
     * @param $parent
     *
     * @return mixed
     */
    public function updateRating($id, $data, Model $parent = null)
    {
        return (new Rating())->updateRating($id, $data);
    }

    /**
     *
     * @param $id
     * @param $sort
     * @return mixed
     */
    public function getAllRatings($id, $sort = 'desc')
    {
        return (new Rating())->getAllRatings($id, $sort);
    }

    /**
     *
     * @param $id
     * @param $sort
     * @return mixed
     */
    public function getApprovedRatings($id, $sort = 'desc')
    {
        return (new Rating())->getApprovedRatings($id, $sort);
    }

    /**
     *
     * @param $id
     * @param $sort
     * @return mixed
     */
    public function getNotApprovedRatings($id, $sort = 'desc')
    {
        return (new Rating())->getNotApprovedRatings($id, $sort);
    }

    /**
     * @param $id
     * @param $limit
     * @param $sort
     * @return mixed
     */
    public function getRecentRatings($id, $limit = 5, $sort = 'desc')
    {
        return (new Rating())->getRecentRatings($id, $limit,  $sort);
    }

    /**
     * @param $id
     * @param $limit
     * @param $approved
     * @param $sort
     * @return mixed
     */
    public function getRecentUserRatings($id, $limit = 5, $approved = true, $sort = 'desc')
    {
        return (new Rating())->getRecentUserRatings($id, $limit, $approved, $sort);
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function deleteRating($id)
    {
        return (new Rating())->deleteRating($id);
    }
}
