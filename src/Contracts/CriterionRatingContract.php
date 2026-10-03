<?php

namespace Codebyray\ReviewRateable\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Optional criterion-specific queries without changing the existing service contract.
 */
interface CriterionRatingContract extends ReviewRateableContract
{
    public function setModel(mixed $model): self;

    public function ratingCountsForKey(string $key, ?string $department = 'default', bool $approved = true): array;

    public function ratingStatsForKey(string $key, ?string $department = 'default', bool $approved = true): array;

    public function getReviewsByRatingForKey(
        ?int $starValue,
        string $key,
        string $department = 'default',
        bool $approved = true,
        bool $withRatings = true
    ): Collection;
}
