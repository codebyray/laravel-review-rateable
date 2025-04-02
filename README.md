# Laravel Review Rateable
<img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/codebyray/laravel-review-rateable"> <img alt="GitHub" src="https://img.shields.io/github/license/codebyray/laravel-review-rateable"> <img alt="GitHub release (latest SemVer)" src="https://img.shields.io/github/v/release/codebyray/laravel-review-rateable"> <img alt="TravisCI" src="https://api.travis-ci.com/codebyray/laravel-review-rateable.svg?branch=master">

NOTE: This is a complete rewrite from v1. It is not compatible with previous versions of this package.


Laravel Review Ratable is a flexible package that enables you to attach reviews (with multiple ratings) to any Eloquent model in your Laravel application. The package supports multiple departments, configurable rating boundaries, review approval, and a decoupled service contract so you can easily integrate, test, and extend the functionality.

## Features

- **Polymorphic Reviews & Ratings:** Attach written reviews along with multiple rating values to any model.
- **Configurable Settings:** Define custom rating keys, labels, and value boundaries (min/max) via a config file.
- **Department Support:** Organize ratings by department (e.g. default, sales, support) with their own criteria.
- **Review Approval:** Set a default approval status for reviews (and override per review if needed).
- **Flexible Data Retrieval:** Retrieve reviews with or without ratings, filter by approval status, and calculate averages.
- **Service Contract:** Use a dedicated service that implements a contract for a decoupled, testable API.

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, or 12

## Installation

### 1. Install via Composer

In your Laravel application's root, require the package via Composer.

```
composer require codebyray/laravel-review-rateable:^2.0
```

### 2. Publish Package Assets
After installation, publish the package config and migration files:

```php
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=config
```
```php
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=migrations
```
Run the migrations to create the necessary database tables:
```php
php artisan migrate
```

## Configuration

The package configuration file is located at config/laravel-review-ratable.php. Here you can adjust global settings such as:

- Rating Value Boundaries:
    - min_rating_value: Minimum rating value.
    - max_rating_value: Maximum rating value.
- Review Approval:
    - review_approved: Default approval status for new reviews.
- Departments & Rating Labels: Define multiple departments, each with its own set of rating keys and labels.

### Example configuration:
```php
<?php

return [
    'min_rating_value' => 1,
    'max_rating_value' => 10,
    'review_approved'  => false, // Reviews will be unapproved by default

    'departments' => [
        'default' => [
            'ratings' => [
                'overall'          => 'Overall Rating',
                'customer_service' => 'Customer Service Rating',
                'quality'          => 'Quality Rating',
                'price'            => 'Price Rating',
                'recommendation'   => 'Would Recommend?',
            ],
        ],
        'sales' => [
            'ratings' => [
                'overall'        => 'Overall Rating',
                'communication'  => 'Communication Rating',
                'follow_up'      => 'Follow-Up Rating',
                'recommendation' => 'Would Recommend?',
            ],
        ],
        'support' => [
            'ratings' => [
                'overall'        => 'Overall Rating',
                'speed'          => 'Response Speed',
                'knowledge'      => 'Knowledge Rating',
                'recommendation' => 'Would Recommend?',
            ],
        ],
    ],
];
```

-----

## Usage
### Making a Model Reviewable
To allow a model to be reviewed, add the ``ReviewRateable`` trait to your model. For example, in your ``Product`` model:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Codebyray\ReviewRateable\Traits\ReviewRateable;

class Product extends Model
{
    use ReviewRateable;
}
```
### Adding a Review
You can add a review (with ratings) directly via the trait:
```php
$product = Product::find(1);

$product->addReview([
    'review'     => 'Great product! The quality is superb and customer service was excellent.',
    'department' => 'sales',   // Optional, defaults to 'default'
    'recommend'  => true,      // Whether the user would recommend the product being reviewed
    'approved'   => true,      // Optionally override default (false) approval by providing 'approved'
    'ratings'    => [
        'overall'        => 5,
        'communication'  => 5,
        'follow_up'      => 5,
        'price'          => 5,
    ],
], auth()->id());
```

### Update a rating
```php
// Retrieve the product you want to update the review for.
$product = Product::findOrFail(1);

// Prepare the updated data.
$data = [
    'review'     => 'Updated review text',    // New review text.
    'department' => 'default',     // Optionally, change the department.
    'recommend'  => false,         // Update recommendation flag.
    'approved'   => true,          // Update approval status if needed.
    'ratings'    => [
        'overall'        => 4,
        'communication'  => 3,
        'follow_up'      => 4,
        'price'          => 2,
    ],
];

// Call the updateReview method on the product.
$product->updateReview($reviewId, $data);
```
### Marking review as approved
```php
// Retrieve the product you want to mark as approved
$product = Product::findOrFail(1);

// Approve th review
$product->approveReview($reviewId);
```
### Delete a review/rating:
```php
// Retrieve the product with the review you want to delete
$product = Product::findOrFail(1);

// Delete the review
$product->deleteReview($reviewId);
```

### Fetch approved or not approved reviews/ratings for a particular resource
```php
// Approved reviews with ratings
$product = Product::findOrFail($postId);

// Get approved reviews (with related ratings)
// Default: approved = true, withRatings = true
$product->getReviews();

// Get not approved reviews (with related ratings)
$product->getReviews(false);
$product->getReviews(approved: false);

// Get approved reviews (without related ratings)
$product->getReviews(true, false);
$product->getReviews(withRatings: false);
```
### Fetch the average rating:
````php
// In progress
````

or

````php
// In progress
````

### Get all ratings:
```php
// In progress
```

### Count total rating:
````php
// In progress
````

### Notes

