# Laravel Review Rateable

[![Packagist downloads](https://img.shields.io/packagist/dt/codebyray/laravel-review-rateable)](https://packagist.org/packages/codebyray/laravel-review-rateable) [![Latest release](https://img.shields.io/github/v/release/codebyray/laravel-review-rateable)](https://github.com/codebyray/laravel-review-rateable/releases) [![Tests](https://github.com/codebyray/laravel-review-rateable/actions/workflows/tests.yml/badge.svg)](https://github.com/codebyray/laravel-review-rateable/actions/workflows/tests.yml) [![License: MIT](https://img.shields.io/github/license/codebyray/laravel-review-rateable)](LICENSE)

Laravel Review Rateable is an MIT-licensed package for attaching written reviews and multiple rating criteria to any Eloquent model. Core provides the review engine and APIs; your application owns its interface, authentication, authorization, and upload flow.

**[Core documentation](https://reviewrateable.com/docs/core) · [Live demo](https://reviewrateable.com/demo) · [Packagist](https://packagist.org/packages/codebyray/laravel-review-rateable)**

> **Upgrading from v1?** Version 2 is an architectural rewrite, not an in-place upgrade. Plan a data migration and update your integration to the new trait and service contract. See the [installation guide](https://reviewrateable.com/docs/core/installation).

## Features

- Reviews and multiple configurable rating criteria on any Eloquent model
- Departments with their own rating keys and labels
- Approval controls and approved-review queries
- Optional, ordered review images with application-supplied thumbnails
- Averages, distributions, counts, and a decoupled service contract

## Requirements

- PHP 8.1 or higher, subject to your Laravel version's PHP requirements
- Laravel 10, 11, 12, or 13
- Compatible numeric IDs for reviewable models and users; the current Core schema does not support UUID identity

Use an upstream-supported PHP version and the latest security patches for Laravel 12 or 13 for new deployments. Laravel 10/11 and PHP 8.1 remain historical compatibility targets; passing their tests does not provide upstream security support. See [maintenance upgrades](UPGRADING.md).

## Installation

In your Laravel application:

```bash
composer require codebyray/laravel-review-rateable:^2.3

php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=config
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=migrations
php artisan migrate
```

Laravel discovers the service provider automatically. Publishing reuses an existing migration filename and leaves it unchanged unless you explicitly pass `--force`. Review images use a [separate opt-in migration](https://reviewrateable.com/docs/core/photos), so the basic installation creates only reviews and ratings tables.

For upgrades, configuration, and production considerations, see the [full installation guide](https://reviewrateable.com/docs/core/installation).

## Quick start

Add the trait to a saved Eloquent model:

```php
namespace App\Models;

use Codebyray\ReviewRateable\Traits\ReviewRateable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use ReviewRateable;
}
```

Then create a review using keys from the configured `default` department:

```php
$review = $product->addReview([
    'review' => 'Comfortable and well made.',
    'department' => 'default',
    'recommend' => true,
    'ratings' => [
        'overall' => 5,
        'customer_service' => 5,
        'quality' => 5,
        'price' => 4,
    ],
], auth()->id());

$publishedReviews = $product->getReviews(); // Approved reviews with ratings.
```

To build a distribution or filter for one criterion, use the key-aware query helpers:

```php
$overallCounts = $product->ratingCountsForKey('overall', 'default');
$overallStats = $product->ratingStatsForKey('overall', 'default');
$fiveStarOverallReviews = $product->getReviewsByRatingForKey(5, 'overall', 'default');
```

The same helpers are available from the injectable `CriterionRatingContract` after calling `setModel($product)`. This extended contract preserves compatibility with existing `ReviewRateableContract` implementations. The counts and statistics include approved reviews by default; pass `approved: false` for private pending-review queries. Pass `null` as the department to count a key across all departments. The existing `ratingCounts()`, `ratingStats()`, and `getReviewsByRating()` methods keep their all-criteria behavior.

In an HTTP endpoint, authenticate the author, authorize the product, and validate the text and configured rating keys before calling `addReview()`. New reviews are unapproved by default. See the [review API guide](https://reviewrateable.com/docs/core/review) for a controller example, updates, approval, deletion, and the injectable service.

## Full documentation

The [Core documentation](https://reviewrateable.com/docs/core) is the maintained reference for [configuration](https://reviewrateable.com/docs/core/configuration), [review queries and statistics](https://reviewrateable.com/docs/core/queries-and-statistics), and [review images, galleries, and file lifecycle](https://reviewrateable.com/docs/core/photos).

## Testing

```bash
composer install
composer test
composer validate --strict
composer audit
```

CI tests Laravel 10–13 and runs a separate MySQL regression job. Security audits gate the maintained Laravel 12/13 lanes; historical Laravel 10/11 lanes allow resolving dependencies with known upstream advisories solely to check compatibility.

## License and contributions

Core is [MIT licensed](LICENSE). Bug reports, questions, and contributions are welcome through [GitHub issues](https://github.com/codebyray/laravel-review-rateable/issues).

## Optional companion

[![Black Friday and Cyber Monday 2026: save 50% on new ReviewRateable Pro licenses with CYBERWEEK2026](.github/assets/cyberweek-2026.svg)](https://reviewrateable.com/pricing)

[ReviewRateable Pro](https://reviewrateable.com/pro) adds ready-made Livewire, React, and Vue interfaces and application workflows such as moderation, replies, and invitations. It is an optional commercial package; Core works independently and includes the review, rating, statistics, and image APIs under the MIT license.

> **Black Friday + Cyber Monday 2026 — 50% off your first Pro purchase.**
> Use **`CYBERWEEK2026`** at checkout from **Thanksgiving, November 26 at 6:00 p.m. through November 30 at 11:59 p.m. Pacific (PST)**.
> Pro Developer: **$49.50 USD** (regularly $99). Pro Agency: **$149.50 USD** (regularly $299).
> New Pro licenses only, including the first 12 months of updates. Renewals are not discounted; your license remains perpetual. [View the offer and plans](https://reviewrateable.com/pricing).
