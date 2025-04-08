<?php

namespace Codebyray\ReviewRateable\Tests;

use Codebyray\ReviewRateable\Models\Review;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Codebyray\ReviewRateable\Traits\ReviewRateable;

// Set up an in-memory database and create the necessary tables.
beforeEach(function () {
    // Configure the in-memory SQLite connection.
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
    ]);

    // Create the reviews table.
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

    // Create the ratings table.
    Schema::create('ratings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('review_id');
        $table->string('key');
        $table->unsignedTinyInteger('value');
        $table->timestamps();
    });

    // Create a dummy table for our model.
    Schema::create('dummy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->timestamps();
    });
});

// Create a dummy model that uses the ReviewRateable trait.
$dummyModel = new class extends Model {
    use ReviewRateable;
    protected $table = 'dummy_models';
    protected $guarded = [];
};

it('adds a review with ratings successfully', function () use ($dummyModel) {
    // Create an instance of the dummy model.
    $instance = $dummyModel::create(['name' => 'Test Add Review']);

    // Add a review with multiple ratings.
    $review = $instance->addReview(
        [
            'review' => 'This is a test review.',
            'department' => 'default',
            'recommend' => true,
            'approved' => true,
            'ratings' => [
              'overall' => 5,
              'customer_service' => 4,
              'quality' => 5,
              'price' => 3,
            ],
        ], 1
    );

    // Assert the review is an instance of the Review model.
    expect($review)->toBeInstanceOf(Review::class)
        ->and($review->review)->toEqual('This is a test review.')
        ->and($review->approved)->toBeTrue();

    // Verify that ratings are attached.
    $ratings = $review->ratings;
    expect($ratings)->toHaveCount(4);

    $overall = $ratings->firstWhere('key', 'overall');
    expect($overall->value)->toEqual(5);
});

it('adds a review without ratings successfully', function () use ($dummyModel) {
    // Create an instance of the dummy model.
    $instance = $dummyModel::create(['name' => 'Test Add Review']);

    // Add a review with multiple ratings.
    $review = $instance->addReview(
        [
            'review' => 'This is a test review.',
            'department' => 'default',
            'recommend' => true,
            'approved' => true,
        ], 1
    );

    // Assert the review is an instance of the Review model.
    expect($review)->toBeInstanceOf(Review::class)
        ->and($review->review)->toEqual('This is a test review.')
        ->and($review->approved)->toBeTrue();

    // Verify that ratings are not attached.
    $ratings = $review->ratings;
    expect($ratings)->toHaveCount(0);
});

it('updates a review and its ratings successfully', function () use ($dummyModel) {
    // Create a dummy model instance.
    $instance = $dummyModel::create(['name' => 'Test Update']);

    // Add an approved review with a rating.
    $review = $instance->addReview(
        [
            'review'   => 'Original review text',
            'approved' => true,
            'ratings'  => [
                'overall' => 5,
            ],
        ]
    );

    // Update the review text and the rating.
    $updateData = [
        'review' => 'Updated review text',
        'ratings' => [
            'overall' => 4,
        ],
    ];

    $updated = $instance->updateReview($review->id, $updateData);
    expect($updated)->toBeTrue();

    $updatedReview = $instance->reviews()->find($review->id);
    expect($updatedReview->review)->toEqual('Updated review text')
        ->and($updatedReview->ratings()->first()->value)->toEqual(4);
});

it('approves a review successfully', function () use ($dummyModel) {
    // Create a dummy model instance.
    $instance = $dummyModel::create(['name' => 'Test Approve']);

    // Add a review that is initially not approved.
    $review = $instance->addReview(
        [
            'review'   => 'Review pending approval',
            'approved' => false,
            'ratings'  => [
                'overall' => 4,
            ],
        ]
    );

    // Verify that the review is not approved initially.
    expect($review->approved)->toBeFalse();

    // Call the approveReview method.
    $result = $instance->approveReview($review->id);
    expect($result)->toBeTrue();

    // Retrieve the review from the database and verify that it's approved.
    $approvedReview = $instance->reviews()->find($review->id);
    expect((bool)$approvedReview->approved)->toBeTrue();
});

it('retrieves approved reviews with ratings by default', function () use ($dummyModel) {
    // Create a dummy model instance.
    $dummyInstance = $dummyModel::create(['name' => 'Test']);

    // Add an approved review with ratings.
    $dummyInstance->addReview(
        [
            'review'   => 'Approved review',
            'approved' => true,
            'ratings'  => [
                'overall' => 5,
            ],
        ]
    );

    // Add a non-approved review.
    $dummyInstance->addReview(
        [
            'review'   => 'Not approved review',
            'approved' => false,
            'ratings'  => [
                'overall' => 3,
            ],
        ]
    );

    // By default, getReviews() retrieves only approved reviews with ratings eager loaded.
    $reviews = $dummyInstance->getReviews();
    expect($reviews)->toHaveCount(1);
    $firstReview = $reviews->first();
    expect($firstReview->relationLoaded('ratings'))->toBeTrue();
});

it('retrieves reviews without ratings when withRatings is false', function () use ($dummyModel) {
    $dummyInstance = $dummyModel::create(['name' => 'Test2']);

    // Add an approved review.
    $dummyInstance->addReview(
        [
            'review'   => 'Review without eager loaded ratings',
            'approved' => true,
            'ratings'  => [
                'overall' => 4,
            ],
        ]
    );

    // Retrieve reviews without eager loading the ratings.
    $reviews = $dummyInstance->getReviews(true, false);
    expect($reviews)->toHaveCount(1);
    $firstReview = $reviews->first();
    expect($firstReview->relationLoaded('ratings'))->toBeFalse();
});

it('retrieves reviews filtered by approved flag', function () use ($dummyModel) {
    $dummyInstance = $dummyModel::create(['name' => 'Test3']);

    $dummyInstance->addReview(
        [
            'review'   => 'Approved review',
            'approved' => true,
            'ratings'  => [
                'overall' => 5,
            ],
        ]
    );

    $dummyInstance->addReview(
        [
            'review'   => 'Not approved review',
            'approved' => false,
            'ratings'  => [
                'overall' => 2,
            ],
        ]
    );

    // Retrieve approved reviews.
    $approvedReviews = $dummyInstance->getReviews(true);
    expect($approvedReviews)->toHaveCount(1);

    // Retrieve non-approved reviews.
    $nonApprovedReviews = $dummyInstance->getReviews(false);
    expect($nonApprovedReviews)->toHaveCount(1);
});

it('allows service getReviews to delegate with parameters correctly', function () use ($dummyModel) {
    $dummyInstance = $dummyModel::create(['name' => 'Test4']);

    $dummyInstance->addReview(
        [
            'review'   => 'Review with ratings',
            'approved' => true,
            'ratings'  => [
                'overall' => 4,
            ],
        ]
    );

    // Retrieve reviews with ratings eager loaded.
    $reviewsWith = $dummyInstance->getReviews(true, true);
    expect($reviewsWith->first()->relationLoaded('ratings'))->toBeTrue();

    // Retrieve reviews without eager loading ratings.
    $reviewsWithout = $dummyInstance->getReviews(true, false);
    expect($reviewsWithout->first()->relationLoaded('ratings'))->toBeFalse();
});

it('deletes a review successfully', function () use ($dummyModel) {
    // Create an instance of the dummy model.
    $dummyInstance = $dummyModel::create(['name' => 'Test Delete']);

    // Add an approved review with ratings.
    $review = $dummyInstance->addReview(
        [
            'review'   => 'Review to delete',
            'approved' => true,
            'ratings'  => [
                'overall' => 5,
            ],
        ]
    );

    // Delete the review by its ID.
    $deleted = $dummyInstance->deleteReview($review->id);
    expect($deleted)->toBeTrue();
});
