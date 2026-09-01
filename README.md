# Laravel Review Rateable
<img alt="Packagist Downloads" src="https://img.shields.io/packagist/dt/codebyray/laravel-review-rateable"> <img alt="GitHub" src="https://img.shields.io/github/license/codebyray/laravel-review-rateable"> <img alt="GitHub release (latest SemVer)" src="https://img.shields.io/github/v/release/codebyray/laravel-review-rateable"> [![Tests](https://github.com/codebyray/laravel-review-rateable/actions/workflows/tests.yml/badge.svg)](https://github.com/codebyray/laravel-review-rateable/actions/workflows/tests.yml)

> **NOTE: Breaking Changes**
> This is a complete rewrite from v1. It is not compatible with previous versions of this package. Because v2 is a total architectural overhaul, you cannot simply upgrade; you will need to perform a migration of your existing data and update your implementation to match the new service contract and trait logic.

Laravel Review Ratable is a flexible package that enables you to attach reviews (with multiple ratings) to any Eloquent model in your Laravel application.
The package supports multiple departments, configurable rating boundaries, review approval, and a decoupled service contract so you can easily integrate, test, and extend the functionality.

## Features

- **Reviews & Ratings:** Attach written reviews along with multiple rating values to any model.
- **Configurable Settings:** Define custom rating keys, labels, and value boundaries (min/max) via a config file.
- **Department Support:** Organize ratings by department (e.g. default, sales, support) with their own criteria.
- **Review Approval:** Set a default approval status for reviews (and override per review if needed).
- **Optional Review Images:** Attach multiple ordered images without requiring an image table in applications that do not use the feature.
- **Flexible Data Retrieval:** Retrieve reviews with or without ratings, filter by approval status, and calculate averages.
- **Service Contract:** Use a dedicated service that implements a contract for a decoupled, testable API.

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, 12, or 13

## Installation

### 1. Install via Composer

In your Laravel application's root, require the package via Composer.
```bash
composer require codebyray/laravel-review-rateable:^2.0
```
### 2. Publish Package Assets
After installation, publish the package config and migration files:

```bash
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=config
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=migrations
```
Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

### Optional: review images

Image attachments are an opt-in feature. The default `migrations` publish tag
does **not** publish the `review_images` migration, so applications that do not
need images receive no additional table.

To enable images, publish and run the separate migration:

```bash
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=review-images-migrations
php artisan migrate
```

Make sure the configured filesystem disk is available. When using Laravel's
standard `public` disk, also run `php artisan storage:link`.

## Configuration

You can customize the package behavior by publishing the configuration file:

```bash
php artisan vendor:publish --provider="Codebyray\ReviewRateable\ReviewRateableServiceProvider" --tag=config
```

### User Model Configuration
Ensure the user_model setting in config/review-rateable.php points to your application's User model:

```php
'user_model' => \App\Models\User::class,
```
> Note: If your application uses a custom User model (e.g., App\Models\Account), ensure you update this path accordingly.

### Configuration Options
You can customize the package behavior by modifying config/review-rateable.php:
- User Model: Configure the model used for reviews.
- Rating Value Boundaries:
    - min_rating_value: Minimum rating value.
    - max_rating_value: Maximum rating value.
- Review Approval:
    - approved_review: Default approval status for new reviews.
- Optional Review Images:
    - disk: Laravel filesystem disk used for originals and thumbnails.
    - directory: Base directory for original images.
    - thumbnail_directory: Base directory for application-generated thumbnails.
    - max_count: Maximum images attached to one review.
    - max_file_size: Maximum size of each original or thumbnail, in kilobytes.
    - allowed_mime_types: MIME allowlist. SVG is intentionally excluded by default.
    - delete_files_on_delete: Delete stored files when their image or review is deleted.
- Departments & Rating Labels: Define multiple departments, each with its own set of rating keys and labels.

Example configuration:
```php
<?php

return [
    'user_model'       => \App\Models\User::class,
    'min_rating_value' => 1,
    'max_rating_value' => 5,
    'approved_review'  => false, // Reviews will be unapproved by default

    'images' => [
        'disk' => env('REVIEW_IMAGE_DISK', env('FILESYSTEM_DISK', 'public')),
        'directory' => 'review-images',
        'thumbnail_directory' => 'review-images/thumbnails',
        'max_count' => 10,
        'max_file_size' => 5120,
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ],
        'delete_files_on_delete' => true,
    ],

    'departments' => [
        'default' => [
            'ratings' => [
                'overall'          => 'Overall Rating',
                'customer_service' => 'Customer Service Rating',
                'quality'          => 'Quality Rating',
                'price'            => 'Price Rating',
            ],
        ],
        'sales' => [
            'ratings' => [
                'overall'        => 'Overall Rating',
                'communication'  => 'Communication Rating',
                'follow_up'      => 'Follow-Up Rating',
            ],
        ],
        'support' => [
            'ratings' => [
                'overall'        => 'Overall Rating',
                'speed'          => 'Response Speed',
                'knowledge'      => 'Knowledge Rating',
            ],
        ],
    ],
];
```
-----

## Usage
### Making a Model Reviewable
To allow a model to be reviewed, add the ReviewRateable trait to your model. For example, in your Product model:
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

// Approve the review
$product->approveReview($reviewId);
```
### Delete a review/rating:
```php
// Retrieve the product with the review you want to delete
$product = Product::findOrFail(1);

// Delete the review
$product->deleteReview($reviewId);
```

## Optional Review Images

The package owns image persistence and lifecycle but does not prescribe an
upload form, CSS framework, gallery, or modal. Validate authorization and the
request in your application before calling these methods.

### Add images

Call the image methods on the returned `Review` model:

```php
$review = $product->addReview([
    'review' => $request->string('review')->toString(),
    'ratings' => ['overall' => $request->integer('overall')],
], auth()->id());

foreach ($request->file('images', []) as $image) {
    $review->addImage($image);
}
```

You can add several files and alt text at once:

```php
$review->addImages([
    $request->file('front'),
    [
        'image' => $request->file('back'),
        'alt_text' => 'Rear connections',
    ],
]);
```

The package does not resize images or require an image-processing dependency.
If your application generates a thumbnail, pass it alongside the original:

```php
$reviewImage = $review->addImage(
    image: $request->file('image'),
    altText: 'Monitor viewed from the front',
    thumbnail: $generatedThumbnail,
);
```

`$reviewImage->url()` returns the original URL and
`$reviewImage->thumbnailUrl()` returns the thumbnail URL. When no thumbnail was
supplied, `thumbnailUrl()` safely falls back to the original URL.

### Remove and reorder images

```php
$review->removeImage($imageId);

// Every image ID belonging to this review must appear exactly once.
$review->reorderImages([$thirdImageId, $firstImageId, $secondImageId]);
```

Removing a `ReviewImage` model or deleting its review removes stored files by
default. Cleanup occurs only after a successful database deletion/commit, and a
filesystem cleanup failure is reported without rolling back the database
operation. Set `images.delete_files_on_delete` to `false` if another process
owns file retention. As with other Eloquent model events, bulk query deletions
such as `Review::query()->delete()` bypass cleanup; delete review models or use
the package's `deleteReview()` method when automatic file removal is expected.

### Eager load images

Images are not loaded automatically:

```php
$reviews = $product->reviews()
    ->approved()
    ->with(['ratings', 'images'])
    ->latest()
    ->get();
```

### Amazon-style gallery and modal example

Presentation remains application-owned. This optional Blade/Alpine example
renders ordered thumbnails and opens the full review with previous/next image
controls. Its class names are hooks for your own CSS; none are shipped by the
package.

```php
@foreach ($reviews as $review)
    <article
        class="review"
        x-data="reviewGallery(@js($review->images->map(fn ($image) => [
            'url' => $image->url(),
            'thumbnail' => $image->thumbnailUrl(),
            'alt' => $image->alt_text ?: 'Review image',
        ])->values()))"
    >
        <p>{{ $review->review }}</p>

        <div class="review-thumbnails" aria-label="Review images">
            <template x-for="(image, index) in images" :key="image.url">
                <button type="button" @click="open(index)" :aria-label="`Open image ${index + 1}`">
                    <img :src="image.thumbnail" :alt="image.alt" loading="lazy">
                </button>
            </template>
        </div>

        <dialog x-ref="dialog" class="review-modal" @close="active = 0">
            <button type="button" @click="$refs.dialog.close()" aria-label="Close">&times;</button>

            <div class="review-modal-layout">
                <div class="review-slideshow">
                    <button type="button" @click="previous()" aria-label="Previous image">&larr;</button>
                    <img :src="images[active]?.url" :alt="images[active]?.alt">
                    <button type="button" @click="next()" aria-label="Next image">&rarr;</button>
                </div>

                <div class="review-modal-copy">
                    <p>{{ $review->review }}</p>
                </div>
            </div>
        </dialog>
    </article>
@endforeach

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reviewGallery', (images) => ({
            images,
            active: 0,
            open(index) {
                this.active = index;
                this.$refs.dialog.showModal();
            },
            previous() {
                this.active = (this.active - 1 + this.images.length) % this.images.length;
            },
            next() {
                this.active = (this.active + 1) % this.images.length;
            },
        }));
    });
</script>
```

For public uploads, continue to enforce application-level authorization and
Laravel request validation. Consider re-encoding uploaded images to strip EXIF
and GPS metadata, generating smaller thumbnails, moderating content, and using
temporary uploads when review creation and file processing are separate steps.
Filesystem writes cannot be rolled back by a database transaction; if your
application calls `addImage()` inside a larger transaction that may later roll
back, arrange application-level cleanup for those newly stored files.

### Fetch approved or not approved reviews/ratings for a particular resource
```php
// Approved reviews with ratings
$product = Product::findOrFail($productId);

// Get approved reviews (with related ratings)
$product->getReviews();

// Get not approved reviews (with related ratings)
$product->getReviews(false);

// Get approved reviews (without related ratings)
$product->getReviews(true, false);
```
### Fetch approved or not approved reviews/ratings by department
```php
$product = Product::findOrFail($productId);

// Get approved reviews by department (with related ratings)
$product->getReviewsByDepartment("sales");

// Get not approved reviews by department
$product->getReviewsByDepartment("sales", false);
```
### Get reviews/ratings based on a star rating
```php
$product = Product::findOrFail($productId);

// Get all 5-star reviews/ratings for the "support" department.
$product->getReviewsByRating(5, department: "support");
```
### Get the total number of reviews
```php
$product = Product::findOrFail($productId);

// Get total for the resource
$product->totalReviews();

// Get total for a specific department
$product->totalDepartmentReviews(department: "sales");
```
### Fetch the average rating
```php
$product = Product::findOrFail($productId);

// Get average rating for a specific key
$overallAverage = $product->averageRating('overall');

// Get all average ratings for all keys
$allAverages = $product->averageRatings();

// Get overall average across all ratings
$overallRating = $product->overallAverageRating();
```
### Count the total number of reviews
```php
$product = Product::find($productId);

$totalReviews = $product->totalReviews();
$totalDepartmentReviews = $product->totalDepartmentReviews();
```
### Return rating distribution (value => count)
```php
$product = Product::find($productId);

// Returns array where key is star rating and value is count
$totalReviews = $product->ratingCounts();
```
### Return ratings stats (counts, percentages, total)
```php
$product = Product::find($productId);

$totalReviews = $product->ratingStats();
```
## Example Usage in a Controller
```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Codebyray\ReviewRateable\Contracts\ReviewRateableContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    protected ReviewRateableContract $reviewService;

    public function __construct(ReviewRateableContract $reviewService)
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
            'ratings'    => [
                "overall"       => $request->input('overall'),
                "communication" => $request->input('communication'),
                "follow_up"     => $request->input('follow_up'),
                "price"         => $request->input('price')
            ],
        ];

        $review = $this->reviewService->addReview($data, auth()->id());

        return response()->json(['message' => 'Review added!', 'review' => $review]);
    }
}
```
## Testing
```bash
composer test
```
