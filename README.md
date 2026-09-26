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

## ReviewRateable Pro

[![ReviewRateable Pro — ready-made review interfaces for Livewire, React, and Vue](.github/assets/pro-banner.svg)](https://reviewrateable.com/pro)

**Core is complete, MIT licensed, and works independently.** [ReviewRateable Pro](https://reviewrateable.com/pro) is an optional commercial companion built on the same review data. It adds ready-made Livewire, React, and Vue interfaces plus moderation, replies, invitations, verified reviews, helpful voting, and other application workflows.

## Requirements

- PHP 8.1 or higher, subject to your Laravel version's PHP requirements
- Laravel 10, 11, 12, or 13
- Compatible numeric IDs for reviewable models and users; the current Core schema does not support UUID identity

## Installation

In your Laravel application:

```bash
composer require codebyray/laravel-review-rateable:^2.2

php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=config
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=migrations
php artisan migrate
```

Laravel discovers the service provider automatically. Publish each migration once; publishing again can create duplicate timestamped migrations. Review images use a [separate opt-in migration](https://reviewrateable.com/docs/core/photos), so the basic installation creates only reviews and ratings tables.

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

In an HTTP endpoint, authenticate the author, authorize the product, and validate the text and configured rating keys before calling `addReview()`. New reviews are unapproved by default. See the [review API guide](https://reviewrateable.com/docs/core/review) for a controller example, updates, approval, deletion, and the injectable service.

## Full documentation

The [Core documentation](https://reviewrateable.com/docs/core) is the maintained reference for [configuration](https://reviewrateable.com/docs/core/configuration), [review queries and statistics](https://reviewrateable.com/docs/core/queries-and-statistics), and [review images, galleries, and file lifecycle](https://reviewrateable.com/docs/core/photos).

## Testing

```bash
composer test
```

## License and contributions

Core is [MIT licensed](LICENSE). Bug reports, questions, and contributions are welcome through [GitHub issues](https://github.com/codebyray/laravel-review-rateable/issues).
