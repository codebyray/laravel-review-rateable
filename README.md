# Laravel Review Rateable
Review Rateable system for laravel 5. This package was originally created and forked from https://github.com/Trexology/laravel-reviewRateable.

## Installation

First, pull in the package through Composer.

```
composer require codebyray/laravel-review-rateable
```

And then include the service provider within `app/config/app.php`.

```php
'providers' => [
    CodebyRay\ReviewRateable\ReviewRateableServiceProvider::class
];
```

At last you need to publish and run the migration.
```
php artisan vendor:publish --provider="CodebyRay\ReviewRateable\ReviewRateableServiceProvider"
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

use CodebyRay\ReviewRateable\Contracts\ReviewRateable;
use CodebyRay\ReviewRateable\Traits\ReviewRateable as ReviewRateableTrait;
use Illuminate\Database\Eloquent\Model;

class Post extends Model implements ReviewRateable
{
    use ReviewRateableTrait;
}
```

### Create a rating
```php
$user = User::first();
$post = Post::first();

$rating = $post->rating([
    title' => 'This is a test title',
    'body' => 'And we will add some shit here',
    'customer_service_rating' => 5,
    'quality_rating' => 5,
    'friendly_rating' => 5,
    'price_rating' => 5,
    'rating' => 5,
    'recommend' => 'Yes',
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
    'price_rating' => 4,
    'rating' => 4,
    'recommend' => 'No',
]);
```

### Delete a rating:
```php
$post->deleteRating(1);
```

### Fetch the average rating:
````php
$post->averageRating()
````

or

````php
$post->averageRating(2) //round to 2 decimal place
````

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
