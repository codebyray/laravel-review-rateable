# Laravel Review Rateable
Review Rateable system for laravel 5. and 6. You can rate your models by:
- Overall Rating
- Customer Service Rating
- Quality Rating
- Friendly Rating
- Price Rating

You can also set whether the model being rated is recommended.

## Installation

First, pull in the package through Composer.

```
composer require codebyray/laravel-review-rateable
```

And then include the service provider within `app/config/app.php`. Note: If you are running Laravel 5.5+ this will be auto loaded for you.

```php
'providers' => [
    Codebyray\ReviewRateable\ReviewRateableServiceProvider::class
];
```

At last you need to publish and run the migration.
```
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag="migrations"
```

Run the migration
```
php artisan migrate
```

-----

### Setup a Model
```php
<?php

namespace App;

use Codebyray\ReviewRateable\Contracts\ReviewRateable;
use Codebyray\ReviewRateable\Traits\ReviewRateable as ReviewRateableTrait;
use Illuminate\Database\Eloquent\Model;

class Post extends Model implements ReviewRateable
{
    use ReviewRateableTrait;
}
```

### Create a rating
When creating a rating you can specify whether the rating is approved or not by adding approved to the array. This is optional and if left 
out the default is not approved to allow for review before posting.
```php
$user = User::first();
$post = Post::first();

$rating = $post->rating([
    'title' => 'This is a test title',
    'body' => 'And we will add some shit here',
    'customer_service_rating' => 5,
    'quality_rating' => 5,
    'friendly_rating' => 5,
    'pricing_rating' => 5,
    'rating' => 5,
    'recommend' => 'Yes',
    'approved' => true, // This is optional and defaults to false
], $user);

dd($rating);
```

### Update a rating
```php
$rating = $post->updateRating(1, [
    'title' => 'new title',
    'body' => 'new body',
    'customer_service_rating' => 1,
    'quality_rating' => 1,
    'friendly_rating' => 3,
    'pricing_rating' => 4,
    'rating' => 4,
    'recommend' => 'No',
    'approved' => true, // This is optional and defaults to false
]);
```
### Marking review as approved
```php
$rating = $post->updateRating(1, ['approved' => true]);
```
### Delete a rating:
```php
$post->deleteRating(1);
```

### Fetch approved or not approved reviews/ratings for a particular resource
```php
// Get not approved ratings
$ratings = $post->getApprovedRatings($post->id, 'desc');

// Get not approved ratings
$ratings = $post->getNotApprovedRatings($post->id, 'desc');

// Get all ratings whether approved or not
$ratings = $post->getAllRatings($post->id, 'desc');

// Get the most recent ratings (limit and sort are optional)
// Limit default is 5, sort default is desc
$ratings = $post->getRecentRatings($post->id, 5, 'desc');

// Get the most recent user ratings (limit and sort are optional)
// Limit default is 5, approved default is true, sort default is desc
$userRatings = $post->getRecentUserRatings($user->id, 5, true, 'desc');

```
### Fetch the average rating:
````php
// Get Overall Average Rating
$post->averageRating()

// Get Customer Service Average Rating
$post->averageCustomerServiceRating()

// Get Quality Average Rating
$post->averageQualityRating()

// Get Friendly Average Rating
$post->averageFriendlyRating()

// Get Price Average Rating
$post->averagePricingRating()

````

or

````php
$post->averageRating(2) //round to 2 decimal place
````

### Get all ratings:
```php
$post = Post::first();

$ratings = $post->getAllRatings($post->id);
```

### Count total rating:
````php
$post->countRating()
````

### Fetch the rating percentage.
This is also how you enforce a maximum rating value.
````php
$post->ratingPercent()

$post->ratingPercent(10)); // Ten star rating system
// Note: The value passed in is treated as the maximum allowed value.
// This defaults to 5 so it can be called without passing a value as well.
````

### Note
This is a fork from Trexology's - [Original Code - laravel-reviewRateable
](https://github.com/Trexology/laravel-reviewRateable).

Please note that this code is not used in the original and is not maintained.
