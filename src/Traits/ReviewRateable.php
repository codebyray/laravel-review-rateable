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
     *
     * @param $round
     * @param $onlyApproved
     *
     * @return double
     */
    public function averageRating($round= null, $onlyApproved= false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];
        $avgExpression = null;

        if ($round) {
            $avgExpression = 'ROUND(AVG(rating), ' . $round . ') as averageReviewRateable';
        } else {
            $avgExpression = 'AVG(rating) as averageReviewRateable';
        }

        return $this->ratings()
            ->selectRaw($avgExpression)
            ->where($where)
            ->get()
            ->first()
            ->averageReviewRateable;
    }

    /**
     *
     * @var $round
     * @var $onlyApproved
     *
     * @return double
     */
    public function averageCustomerServiceRating($round= null, $onlyApproved= false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        $avgExpression = null;

        if ($round) {
            $avgExpression = 'ROUND(AVG(customer_service_rating), ' . $round . ') as averageCustomerServiceReviewRateable';
        } else {
            $avgExpression = 'AVG(customer_service_rating) as averageCustomerServiceReviewRateable';
        }

        return $this->ratings()
            ->selectRaw($avgExpression)
            ->where($where)
            ->get()
            ->first()
            ->averageCustomerServiceReviewRateable;
    }

    /**
     *
     * @param $round
     * @param $onlyApproved
     *
     * @return double
     */
    public function averageQualityRating($round = null, $onlyApproved= false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        $avgExpression = null;

        if ($round) {
            $avgExpression = 'ROUND(AVG(quality_rating), ' . $round . ') as averageQualityReviewRateable';
        } else {
            $avgExpression = 'AVG(quality_rating) as averageQualityReviewRateable';
        }

        return $this->ratings()
            ->selectRaw($avgExpression)
            ->where($where)
            ->get()
            ->first()
            ->averageQualityReviewRateable;
    }

    /**
     *
     * @var $round
     * @var $onlyApproved
     *
     * @return double
     */
    public function averageFriendlyRating($round = null, $onlyApproved= false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        $avgExpression = null;

        if ($round) {
            $avgExpression = 'ROUND(AVG(friendly_rating), ' . $round . ') as averageFriendlyReviewRateable';
        } else {
            $avgExpression = 'AVG(friendly_rating) as averageFriendlyReviewRateable';
        }

        return $this->ratings()
            ->selectRaw($avgExpression)
            ->where($where)
            ->get()
            ->first()
            ->averageFriendlyReviewRateable;
    }

    /**
     *
     * @var $round
     * @var $onlyApproved
     *
     * @return double
     */
    public function averagePricingRating($round = null, $onlyApproved = false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        $avgExpression = null;

        if ($round) {
            $avgExpression = 'ROUND(AVG(pricing_rating), ' . $round . ') as averagePricingReviewRateable';
        } else {
            $avgExpression = 'AVG(pricing_rating) as averagePricingReviewRateable';
        }

        return $this->ratings()
            ->selectRaw($avgExpression)
            ->where($where)
            ->get()
            ->first()
            ->averagePricingReviewRateable;
    }

    /**
     * @var $onlyApproved
     *
     * @return int
     */
    public function countRating($onlyApproved = false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        return $this->ratings()
            ->selectRaw('count(rating) as countReviewRateable')
            ->where($where)
            ->get()
            ->first()
            ->countReviewRateable;
    }

    /**
     * @var $onlyApproved
     *
     * @return int
     */
    public function countCustomerServiceRating($onlyApproved = false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        return $this->ratings()
            ->selectRaw('count(customer_service_rating) as countCustomerServiceReviewRateable')
            ->where($where)
            ->get()
            ->first()
            ->countCustomerServiceReviewRateable;
    }

    /**
     * @var $onlyApproved
     *
     * @return int
     */
    public function countQualityRating($onlyApproved = false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        return $this->ratings()
            ->selectRaw('count(quality_rating) as countQualityReviewRateable')
            ->where($where)
            ->get()
            ->first()
            ->countQualityReviewRateable;
    }

    /**
     * @var $onlyApproved
     *
     * @return int
     */
    public function countFriendlyRating($onlyApproved = false) 
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        return $this->ratings()
            ->selectRaw('count(friendly_rating) as countFriendlyReviewRateable')
            ->where($where)
            ->get()
            ->first()
            ->countFriendlyReviewRateable;
    }

    /**
     * @var $onlyApproved
     *
     * @return int
     */
    public function countPriceRating($onlyApproved = false) 
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        return $this->ratings()
            ->selectRaw('count(pricing_rating) as countPriceReviewRateable')
            ->where($where)
            ->get()
            ->first()
            ->countPriceReviewRateable;
    }

    /**
     * @var $onlyApproved
     *
     * @return double
     */
    public function sumRating($onlyApproved = false)
    {
        $where = $onlyApproved ? [['approved', '1']] : [];

        return $this->ratings()
            ->selectRaw('SUM(rating) as sumReviewRateable')
            ->where($where)
            ->get()
            ->first()
            ->sumReviewRateable;
    }

    /**
     * @param $max
     *
     * @return double
     */
    public function ratingPercent($max = 5)
    {
        $ratings = $this->ratings();
        $quantity = $ratings->count();
        $total = $ratings->selectRaw('SUM(rating) as total')->get()->first()->total;
        return ($quantity * $max) > 0 ? $total / (($quantity * $max) / 100) : 0;
    }

    /**
     * @param $data
     * @param $author
     * @param $parent
     *
     * @return static
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
     *
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
     *
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
     *
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
     *
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
     *
     * @return mixed
     */
    public function getRecentUserRatings($id, $limit = 5, $approved = true, $sort = 'desc')
    {
        return (new Rating())->getRecentUserRatings($id, $limit, $approved, $sort);
    }

    /**
     * @param $rating
     * @param $type
     * @param $approved
     * @param $sort
     *
     * @return mixed
     */
    public function getCollectionByAverageRating($rating, $type = 'rating', $approved = true, $sort = 'desc')
    {
        return (new Rating())->getCollectionByAverageRating($rating, $approved, $sort);
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

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getUserRatings($id, $author, $sort = 'desc')
    {
        return (new Rating())->getUserRatings($id, $author, $sort = 'desc');
    }
}
