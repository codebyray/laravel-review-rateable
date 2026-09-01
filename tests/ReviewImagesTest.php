<?php

namespace Codebyray\ReviewRateable\Tests;

use Codebyray\ReviewRateable\Models\Review;
use Codebyray\ReviewRateable\Models\ReviewImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

beforeEach(function () {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    config()->set('review-rateable.images.disk', 'review-images-testing');
    config()->set('review-rateable.images.max_count', 3);
    config()->set('review-rateable.images.max_file_size', 5120);

    Storage::fake('review-images-testing');
    DB::statement('PRAGMA foreign_keys = ON');

    Schema::create('reviews', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('reviewable_id');
        $table->string('reviewable_type');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->text('review')->nullable();
        $table->string('department')->default('default');
        $table->boolean('recommend')->default(false);
        $table->boolean('approved')->default(false);
        $table->timestamps();
    });

    Schema::create('review_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
        $table->string('disk');
        $table->string('path');
        $table->string('thumbnail_path')->nullable();
        $table->string('original_name')->nullable();
        $table->string('mime_type', 100);
        $table->unsignedBigInteger('size');
        $table->unsignedInteger('width')->nullable();
        $table->unsignedInteger('height')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->string('alt_text')->nullable();
        $table->timestamps();
        $table->index(['review_id', 'sort_order']);
        $table->unique(['disk', 'path']);
    });

    Schema::create('image_reviewables', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
});

function imageReview(): Review
{
    $model = new class extends Model
    {
        use \Codebyray\ReviewRateable\Traits\ReviewRateable;

        protected $table = 'image_reviewables';

        protected $guarded = [];
    };

    return $model->newQuery()->create()->addReview(['review' => 'With photos']);
}

it('stores multiple ordered images and exposes their URLs', function () {
    $review = imageReview();
    $first = $review->addImage(UploadedFile::fake()->image('front.jpg', 800, 600), 'Front view');
    $second = $review->addImage(UploadedFile::fake()->image('back.png', 640, 480));

    expect($review->images)->toHaveCount(2)
        ->and($first)->toBeInstanceOf(ReviewImage::class)
        ->and($first->sort_order)->toBe(0)
        ->and($second->sort_order)->toBe(1)
        ->and($first->original_name)->toBe('front.jpg')
        ->and($first->width)->toBe(800)
        ->and($first->height)->toBe(600)
        ->and($first->alt_text)->toBe('Front view')
        ->and($first->url())->toContain($first->path)
        ->and($first->thumbnailUrl())->toBe($first->url());

    Storage::disk('review-images-testing')->assertExists([$first->path, $second->path]);
});

it('stores an application-generated thumbnail separately', function () {
    $review = imageReview();
    $image = $review->addImage(
        UploadedFile::fake()->image('photo.jpg', 1200, 900),
        thumbnail: UploadedFile::fake()->image('photo-thumb.jpg', 240, 180)
    );

    expect($image->thumbnail_path)->not->toBeNull()
        ->and($image->thumbnailUrl())->toContain($image->thumbnail_path)
        ->and($image->thumbnailUrl())->not->toBe($image->url());

    Storage::disk('review-images-testing')->assertExists([$image->path, $image->thumbnail_path]);
});

it('adds image batches and enforces the configured maximum count', function () {
    $review = imageReview();
    $images = $review->addImages([
        UploadedFile::fake()->image('one.jpg'),
        [
            'image' => UploadedFile::fake()->image('two.jpg'),
            'alt_text' => 'Second image',
        ],
    ]);

    expect($images)->toHaveCount(2)
        ->and($images->last()->alt_text)->toBe('Second image');

    expect(fn () => $review->addImages([
        UploadedFile::fake()->image('three.jpg'),
        UploadedFile::fake()->image('four.jpg'),
    ]))->toThrow(InvalidArgumentException::class, 'at most 3 images');
});

it('rolls back images already added by a failed batch', function () {
    config()->set('review-rateable.images.delete_files_on_delete', false);
    $review = imageReview();

    expect(fn () => $review->addImages([
        UploadedFile::fake()->image('valid.jpg'),
        [
            'image' => UploadedFile::fake()->image('invalid.jpg'),
            'alt_text' => str_repeat('a', 256),
        ],
    ]))->toThrow(InvalidArgumentException::class, 'alt text');

    expect($review->images()->count())->toBe(0)
        ->and(Storage::disk('review-images-testing')->allFiles())->toBe([]);
});

it('rejects disallowed MIME types and oversized files', function () {
    $review = imageReview();

    expect(fn () => $review->addImage(
        UploadedFile::fake()->create('notes.txt', 10, 'text/plain')
    ))->toThrow(InvalidArgumentException::class, 'MIME type is not allowed');

    config()->set('review-rateable.images.max_file_size', 1);

    expect(fn () => $review->addImage(
        UploadedFile::fake()->image('large.jpg')->size(2)
    ))->toThrow(InvalidArgumentException::class, 'maximum file size');
});

it('reorders every image and rejects incomplete orders', function () {
    $review = imageReview();
    $first = $review->addImage(UploadedFile::fake()->image('one.jpg'));
    $second = $review->addImage(UploadedFile::fake()->image('two.jpg'));
    $third = $review->addImage(UploadedFile::fake()->image('three.jpg'));

    expect($review->reorderImages([$third->id, $first->id, $second->id]))->toBeTrue()
        ->and($review->fresh()->images()->pluck('id')->all())->toBe([
            $third->id,
            $first->id,
            $second->id,
        ]);

    expect(fn () => $review->reorderImages([$first->id, $second->id]))
        ->toThrow(InvalidArgumentException::class, 'every review image ID exactly once');
});

it('removes only owned images and cleans up their files', function () {
    $review = imageReview();
    $otherReview = imageReview();
    $image = $review->addImage(UploadedFile::fake()->image('owned.jpg'));
    $otherImage = $otherReview->addImage(UploadedFile::fake()->image('other.jpg'));

    expect($review->removeImage($otherImage))->toBeFalse()
        ->and($review->removeImage($image))->toBeTrue()
        ->and($review->images()->count())->toBe(0);

    Storage::disk('review-images-testing')->assertMissing($image->path);
    Storage::disk('review-images-testing')->assertExists($otherImage->path);
});

it('cleans up image files when a review is deleted', function () {
    $review = imageReview();
    $image = $review->addImage(
        UploadedFile::fake()->image('original.jpg'),
        thumbnail: UploadedFile::fake()->image('thumbnail.jpg')
    );

    $review->delete();

    Storage::disk('review-images-testing')->assertMissing([$image->path, $image->thumbnail_path]);
    expect(ReviewImage::query()->count())->toBe(0);
});

it('waits for commit before deleting review files', function () {
    $review = imageReview();
    $image = $review->addImage(UploadedFile::fake()->image('rollback.jpg'));

    DB::beginTransaction();
    $review->delete();

    Storage::disk('review-images-testing')->assertExists($image->path);
    DB::rollBack();

    Storage::disk('review-images-testing')->assertExists($image->path);
    expect(Review::query()->find($review->id))->not->toBeNull()
        ->and(ReviewImage::query()->find($image->id))->not->toBeNull();
});

it('can preserve files when automatic cleanup is disabled', function () {
    config()->set('review-rateable.images.delete_files_on_delete', false);
    $review = imageReview();
    $image = $review->addImage(UploadedFile::fake()->image('preserved.jpg'));

    $review->removeImage($image);

    Storage::disk('review-images-testing')->assertExists($image->path);
});

it('supports ordinary eager loading without forcing it on applications', function () {
    $review = imageReview();
    $review->addImage(UploadedFile::fake()->image('eager.jpg'));

    $loaded = Review::query()->with('images')->findOrFail($review->id);

    expect($loaded->relationLoaded('images'))->toBeTrue()
        ->and($loaded->images)->toHaveCount(1);
});

it('publishes the optional image migration under a separate tag', function () {
    $imagePaths = ServiceProvider::pathsToPublish(
        \Codebyray\ReviewRateable\ReviewRateableServiceProvider::class,
        'review-images-migrations'
    );
    $corePaths = ServiceProvider::pathsToPublish(
        \Codebyray\ReviewRateable\ReviewRateableServiceProvider::class,
        'migrations'
    );

    expect($imagePaths)->toHaveCount(1)
        ->and(array_key_first($imagePaths))->toEndWith('create_review_images_table.php.stub')
        ->and(collect(array_keys($corePaths))->contains(
            fn ($path) => str_ends_with($path, 'create_review_images_table.php.stub')
        ))->toBeFalse();
});

it('creates the expected schema from the optional migration', function () {
    Schema::drop('review_images');
    $migration = require __DIR__.'/../database/migrations/create_review_images_table.php.stub';

    $migration->up();

    expect(Schema::hasColumns('review_images', [
        'review_id',
        'disk',
        'path',
        'thumbnail_path',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'sort_order',
        'alt_text',
    ]))->toBeTrue();
});
