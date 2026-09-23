<?php

namespace Codebyray\ReviewRateable\Tests;

use Codebyray\ReviewRateable\Traits\ReviewRateable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('calculates grouped rating averages on MySQL', function () {
    if (! filter_var(env('REVIEW_RATEABLE_MYSQL_TESTS', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('MySQL compatibility tests are disabled.');
    }

    config()->set('database.connections.review-rateable-mysql', [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'review_rateable'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]);
    config()->set('review-rateable.departments.support.ratings', [
        'overall' => 'Overall Rating',
        'quality' => 'Quality Rating',
    ]);

    DB::purge('review-rateable-mysql');
    $schema = Schema::connection('review-rateable-mysql');

    $schema->dropIfExists('ratings');
    $schema->dropIfExists('reviews');
    $schema->dropIfExists('mysql_reviewables');

    $schema->create('reviews', function (Blueprint $table) {
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

    $schema->create('ratings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('review_id');
        $table->string('key');
        $table->unsignedTinyInteger('value');
        $table->timestamps();
    });

    $schema->create('mysql_reviewables', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });

    $model = new class extends Model
    {
        use ReviewRateable;

        protected $connection = 'review-rateable-mysql';

        protected $table = 'mysql_reviewables';

        protected $guarded = [];
    };

    try {
        $instance = $model->newQuery()->create();

        $instance->addReview([
            'approved' => true,
            'department' => 'support',
            'ratings' => ['overall' => 5, 'quality' => 3],
        ]);

        expect($instance->averageRatings())->toMatchArray([
            'overall' => 5.0,
            'quality' => 3.0,
        ])->and($instance->averageRatingsByDepartment('support'))->toMatchArray([
            'overall' => 5.0,
            'quality' => 3.0,
        ]);
    } finally {
        $schema->dropIfExists('ratings');
        $schema->dropIfExists('reviews');
        $schema->dropIfExists('mysql_reviewables');
        DB::purge('review-rateable-mysql');
    }
});
