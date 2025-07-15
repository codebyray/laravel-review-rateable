# Laravel Review Rateable
<img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/codebyray/laravel-review-rateable"> <img alt="GitHub" src="https://img.shields.io/github/license/codebyray/laravel-review-rateable"> <img alt="GitHub release (latest SemVer)" src="https://img.shields.io/github/v/release/codebyray/laravel-review-rateable"> <img alt="TravisCI" src="https://api.travis-ci.com/codebyray/laravel-review-rateable.svg?branch=master">

NOTE: This is a complete rewrite from v1. It is not compatible with previous versions of this package.


Laravel Review Ratable is a flexible package that enables you to attach reviews (with multiple ratings) to any Eloquent model in your Laravel application. 
The package supports multiple departments, configurable rating boundaries, review approval, and a decoupled service contract so you can easily integrate, test, and extend the functionality.

## Features

- **Reviews & Ratings:** Attach written reviews along with multiple rating values to any model.
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

The package configuration file is located at config/review-ratable.php. Here you can adjust global settings such as:

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
### Adding a review/rating(s)
You can add a review (with ratings) directly via the trait:
```php
$product = Product::find($productId);

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

### Update a review/rating(s)
```php
// Retrieve the product you want to update the review for.
$product = Product::findOrFail($productId);

// Prepare the updated data.
$data = [
    'review'     => 'Updated review text',    // New review text.
    'department' => 'sales',       // Optionally, change the department.
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
$product = Product::findOrFail($productId);

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
$product = Product::findOrFail($productId);

// Get approved reviews (with related ratings)
// Default: approved: true, withRatings: true
$product->getReviews();

// Get not approved reviews (with related ratings)
$product->getReviews(false);
$product->getReviews(approved: false);

// Get approved reviews (without related ratings)
$product->getReviews(true, false);
$product->getReviews(withRatings: false);
```

### Fetch approved or not approved reviews/ratings by department
```php
// Approved reviews department with ratings
$product = Product::findOrFail($productId);

// Get approved reviews by department (with related ratings)
// Default: approved: true, withRatings: true
$product->getReviewsByDepartment("sales");

// Get not approved reviews department (with related ratings)
$product->getReviewsByDepartment("sales", false);
$product->getReviewsByDepartment(department: "sales", approved: false);

// Get approved reviews department (without related ratings)
$product->getReviewsByDepartment("sales", true, false);
$product->getReviewsByDepartment(department: "sales", withRatings: false);
```

### Get reviews/ratings based on a star rating.
```php
// Fetch the product
$product = Product::findOrFail($productId);

// Get approved reviews by star rating. The below call will return all 5-star 
// reviews/ratings for the "support" department.
// Defaults: starValue: null, department: "default", approved: true, withRatings: true
$product->getReviewsByRating(5, department: "support");

// If you only want the reviews and not the ratings, add withRatings: false:
$product->getReviewsByRating(5, department: "support", withRatings: false);
```

### Get the total number of reviews.
```php
// Approved reviews department with ratings
$product = Product::findOrFail($productId);

// Get the total number of reviews for the reviewable resource.
// Default: approved = true
$product->totalReviews();

// Get the total number of reviews for the reviewable resource by department.
// Defaults: department = "default", approved = true
$product->totalDepartmentReviews(department: "sales");
```

### Fetch the average rating:
```php
// Retrieve a Product instance (assuming Product uses the ReviewRatable trait)
$product = Product::findOrFail($productId);

// Get the average rating for a specific key ('overall') using approved reviews (default).
$overallAverage = $product->averageRating('overall');
echo "Overall Average (approved): {$overallAverage}\n";

// Get the average rating for a specific key using non-approved reviews.
$nonApprovedOverall = $product->averageRating('overall', false);
echo "Overall Average (pending): {$nonApprovedOverall}\n";

// Get all average ratings for all rating keys from approved reviews.
$allAverages = $product->averageRatings();

// Returns something like
[
    "overall"       => 3.5,
    "communication" => 2.75,
    "follow_up"     => 3.5,
    "price"         => 4.25
]

// Get the overall average rating across all ratings (approved reviews by default).
$overallRating = $product->overallAverageRating();

// Returns float
3.5
```

### Count the total number of reviews:
````php
// Retrieve a Product instance (assuming Product uses the ReviewRatable trait)
$product = Product::find($productId);

// Returns the total number of reviews.
$totalReviews = $product->totalReviews();

// Get the total for a specific department
// Defaults: department: "default", approved = true
$totalDepartmentReviews = $product->totalDepartmentReviews();
````

### Return an array of rating value ⇒ count, for the full model or for a given department.
````php
// Retrieve a Product instance (assuming Product uses the ReviewRatable trait)
$product = Product::find($productId);

// Returns the total number of ratings. 
// Defaults: department: "default", approved = true
$totalReviews = $product->ratingCounts();

// Returns, where the array key is the star rating and the value is the total count
array:5▼
  1 => 0
  2 => 1
  3 => 8
  4 => 0
  5 => 3
]
````

### Returns a muti-denominational array with ratings stats for a given department.
````php
// Retrieve a Product instance (assuming the Product uses the ReviewRatable trait)
$product = Product::find($productId);

// Get the stats for a given department or the default.
// Defaults: department: "default", approved = true
$totalReviews = $product->ratingStats();

// Returns, where the "counts" array holds the count for each star rating.
// And the "percentages" holds to percentage for each star rating.
// Finally, the total number of star ratings is returned.
array:3▼
  "counts" => array:5 [▶
    1 => 0
    2 => 1
    3 => 8
    4 => 0
    5 => 3
  ]
  "percentages" => array:5 [▶
    1 => 0.0
    2 => 8.0
    3 => 67.0
    4 => 0.0
    5 => 25.0
  ]
  "total" => 12
]
````

## Example Usage in a Controller
Every method available using the ReviewRateable Trait can also be called via the service

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use CodeByRay\LaravelReviewRatable\Contracts\ReviewRatableContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    protected ReviewRatableContract $reviewService;

    public function __construct(ReviewRatableContract $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::find($request->input('product_id'));
        $this->reviewService->setModel($product);

        $data = [
            'review'     => $request->input('review'),
            'department' => $request->input('department'),
            'recommend'  => $request->boolean('recommend'),
            // 'approved' is optional and will default to config value.
            'ratings'    => [
                "overall"       => $request->input('overall'),
                "communication" => $request->input('communication'),
                "follow_up"     => $request->input('follow_up'),
                "price"         => $request->input('price')
            ]),
        ];

        $review = $this->reviewService->addReview($data, auth()->id());

        return response()->json(['message' => 'Review added!', 'review' => $review]);
    }

    public function show(Product $product): JsonResponse
    {
        $this->reviewService->setModel($product);

        $reviews = $this->reviewService->getReviews(); // Get approved reviews with ratings
        $total   = $this->reviewService->totalReviews();

        return response()->json(compact('reviews', 'total'));
    }
}
```
## Testing
```bash
composer test
```
### Notes

