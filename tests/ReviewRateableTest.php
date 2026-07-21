<?php

namespace Codebyray\ReviewRateable\Tests;

use Codebyray\ReviewRateable\Contracts\ReviewRateableContract;
use Codebyray\ReviewRateable\Models\Review;
use Codebyray\ReviewRateable\Services\ReviewRateableService;
use Codebyray\ReviewRateable\Traits\ReviewRateable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Set up an in-memory database and create the necessary tables.
beforeEach(function () {
    // Configure the in-memory SQLite connection.
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
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
$dummyModel = new class extends Model
{
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
            'review' => 'Original review text',
            'approved' => true,
            'ratings' => [
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
            'review' => 'Review pending approval',
            'approved' => false,
            'ratings' => [
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
    expect((bool) $approvedReview->approved)->toBeTrue();
});

it('retrieves approved reviews with ratings by default', function () use ($dummyModel) {
    // Create a dummy model instance.
    $dummyInstance = $dummyModel::create(['name' => 'Test']);

    // Add an approved review with ratings.
    $dummyInstance->addReview(
        [
            'review' => 'Approved review',
            'approved' => true,
            'ratings' => [
                'overall' => 5,
            ],
        ]
    );

    // Add a non-approved review.
    $dummyInstance->addReview(
        [
            'review' => 'Not approved review',
            'approved' => false,
            'ratings' => [
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
            'review' => 'Review without eager loaded ratings',
            'approved' => true,
            'ratings' => [
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
            'review' => 'Approved review',
            'approved' => true,
            'ratings' => [
                'overall' => 5,
            ],
        ]
    );

    $dummyInstance->addReview(
        [
            'review' => 'Not approved review',
            'approved' => false,
            'ratings' => [
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
            'review' => 'Review with ratings',
            'approved' => true,
            'ratings' => [
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
            'review' => 'Review to delete',
            'approved' => true,
            'ratings' => [
                'overall' => 5,
            ],
        ]
    );

    // Delete the review by its ID.
    $deleted = $dummyInstance->deleteReview($review->id);
    expect($deleted)->toBeTrue();
});

it('returns false when updateReview is called without arguments', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Null Update']);

    $result = $instance->updateReview();
    expect($result)->toBeFalse();
});

it('returns false when approveReview is called without an id', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Null Approve']);

    $result = $instance->approveReview();
    expect($result)->toBeFalse();
});

it('returns false when deleteReview is called without an id', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Null Delete']);

    $result = $instance->deleteReview();
    expect($result)->toBeFalse();
});

it('handles getReviewsByRating called with no star value gracefully', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Null Star']);

    $instance->addReview(
        [
            'review' => 'Some review',
            'approved' => true,
            'ratings' => ['overall' => 5],
        ]
    );

    $reviews = $instance->getReviewsByRating();
    expect($reviews)->toBeInstanceOf(Collection::class);
});

it('service addReview delegates to model', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Service Add']);
    $service = new ReviewRateableService;
    $service->setModel($instance);

    $review = $service->addReview(
        [
            'review' => 'Via service',
            'approved' => true,
            'ratings' => ['overall' => 5],
        ], 1);

    expect($review)->toBeInstanceOf(Review::class)
        ->and($instance->reviews()->count())->toBe(1);
});

it('service updateReview delegates with id and data', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Service Update']);
    $service = new ReviewRateableService;
    $service->setModel($instance);

    $review = $instance->addReview(
        [
            'review' => 'Original',
            'approved' => true,
            'ratings' => ['overall' => 5],
        ]
    );

    $result = $service->updateReview($review->id, [
        'review' => 'Updated via service',
        'ratings' => ['overall' => 3],
    ]);

    expect($result)->toBeTrue();

    $fresh = $instance->reviews()->find($review->id);
    expect($fresh->review)->toEqual('Updated via service')
        ->and($fresh->ratings()->first()->value)->toEqual(3);
});

it('throws when using service without setting model', function () {
    $service = new ReviewRateableService;

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('No model set in ReviewRateableService');

    $service->getReviews();
});

it('calculates average ratings correctly using database queries', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Test Averages']);

    // Review 1: 5 stars
    $instance->addReview([
        'review' => 'Great!',
        'approved' => true,
        'department' => 'default',
        'ratings' => ['overall' => 5, 'quality' => 5],
    ]);

    // Review 2: 3 stars
    $instance->addReview([
        'review' => 'Okay.',
        'approved' => true,
        'department' => 'default',
        'ratings' => ['overall' => 3, 'quality' => 1],
    ]);

    // Review 3: Unapproved (should be ignored by default)
    $instance->addReview([
        'review' => 'Terrible.',
        'approved' => false,
        'department' => 'default',
        'ratings' => ['overall' => 1, 'quality' => 1],
    ]);

    // Test overall average: (5 + 3 + 5 + 1) / 4 = 14 / 4 = 3.5
    expect($instance->overallAverageRating())->toEqual(3.5);

    // Test specific key average: (5 + 3) / 2 = 4.0
    expect($instance->averageRating('overall'))->toEqual(4.0);

    // Test grouped averages
    $averages = $instance->averageRatings();
    expect($averages)->toHaveKey('overall', 4.0)
        ->and($averages)->toHaveKey('quality', 3.0)
        ->and($instance->averageRatingByDepartment('default', 'overall'))->toEqual(4.0);
});

it('counts total reviews correctly through the service', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Test Counts']);
    $service = new ReviewRateableService;
    $service->setModel($instance);

    $instance->addReview(['review' => 'R1', 'approved' => true, 'department' => 'sales']);
    $instance->addReview(['review' => 'R2', 'approved' => true, 'department' => 'sales']);
    $instance->addReview(['review' => 'R3', 'approved' => true, 'department' => 'support']);

    // Unapproved shouldn't be counted by default
    $instance->addReview(['review' => 'R4', 'approved' => false, 'department' => 'sales']);

    expect($service->totalReviews())->toEqual(3)
        ->and($service->totalDepartmentReviews('sales'))->toEqual(2)
        ->and($service->totalDepartmentReviews('support'))->toEqual(1);
});

it('attaches a user ID to the review when provided', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Test User ID']);

    $review = $instance->addReview(
        [
            'review' => 'Review by user 99',
            'approved' => true,
        ],
        99 // Passing the optional $userId parameter
    );

    expect($review->user_id)->toEqual(99);
});

it('filters unapproved reviews by rating', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Approval Filter']);

    $instance->addReview([
        'review' => 'Approved',
        'approved' => true,
        'ratings' => ['overall' => 5],
    ]);

    $unapproved = $instance->addReview([
        'review' => 'Unapproved',
        'approved' => false,
        'ratings' => ['overall' => 5],
    ]);

    $reviews = $instance->getReviewsByRating(5, approved: false);

    expect($reviews)->toHaveCount(1)
        ->and($reviews->first()->is($unapproved))->toBeTrue();
});

it('resolves independent review rateable service instances', function () {
    $first = app(ReviewRateableContract::class);
    $second = app(ReviewRateableContract::class);

    expect($first)->toBeInstanceOf(ReviewRateableService::class)
        ->and($second)->toBeInstanceOf(ReviewRateableService::class)
        ->and($first)->not->toBe($second);
});

it('casts review booleans and rating values consistently', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Casts']);
    $review = $instance->addReview([
        'approved' => true,
        'recommend' => false,
        'ratings' => ['overall' => 5],
    ])->fresh();

    expect($review->approved)->toBeTrue()
        ->and($review->recommend)->toBeFalse()
        ->and($review->ratings()->first()->value)->toBeInt();
});

it('removes ratings that do not belong to a changed department', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Department Change']);
    $review = $instance->addReview([
        'department' => 'sales',
        'ratings' => [
            'overall' => 4,
            'communication' => 3,
            'price' => 2,
        ],
    ]);

    $updated = $instance->updateReview($review->id, [
        'department' => 'support',
        'ratings' => [
            'overall' => 5,
            'speed' => 4,
            'price' => 1,
        ],
    ]);

    expect($updated)->toBeTrue()
        ->and($review->fresh()->ratings()->pluck('value', 'key')->all())->toBe([
            'overall' => 5,
            'speed' => 4,
        ]);
});

it('rolls back a review when a rating cannot be saved', function () use ($dummyModel) {
    $instance = $dummyModel::create(['name' => 'Transaction']);

    DB::statement(<<<'SQL'
        CREATE TRIGGER fail_rating_insert
        BEFORE INSERT ON ratings
        BEGIN
            SELECT RAISE(FAIL, 'rating insert failed');
        END
        SQL);

    expect(fn () => $instance->addReview([
        'review' => 'Must roll back',
        'ratings' => ['overall' => 5],
    ]))->toThrow(QueryException::class);

    expect($instance->reviews()->count())->toBe(0);
});

it('runs rating aggregates on the reviewable model connection', function () {
    config()->set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    Schema::connection('tenant')->create('reviews', function (Blueprint $table) {
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

    Schema::connection('tenant')->create('ratings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('review_id');
        $table->string('key');
        $table->unsignedTinyInteger('value');
        $table->timestamps();
    });

    Schema::connection('tenant')->create('tenant_models', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    $tenantModel = new class extends Model
    {
        use ReviewRateable;

        protected $table = 'tenant_models';

        protected $guarded = [];
    };
    $tenantModel->setConnection('tenant');
    $instance = $tenantModel->newQuery()->create();

    $instance->addReview([
        'approved' => true,
        'ratings' => ['overall' => 5, 'quality' => 3],
    ]);

    expect($instance->averageRating('overall'))->toEqual(5.0)
        ->and($instance->overallAverageRating())->toEqual(4.0)
        ->and($instance->averageRatings())->toMatchArray([
            'overall' => 5.0,
            'quality' => 3.0,
        ])
        ->and($instance->ratingCounts())->toMatchArray([
            1 => 0,
            2 => 0,
            3 => 1,
            4 => 0,
            5 => 1,
        ]);
});
