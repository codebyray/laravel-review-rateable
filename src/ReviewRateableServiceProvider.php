<?php

namespace Codebyray\ReviewRateable;

use Codebyray\ReviewRateable\Contracts\ReviewRateableContract;
use Codebyray\ReviewRateable\Services\ReviewRateableService;
use Illuminate\Support\ServiceProvider;

class ReviewRateableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Publish migration stubs.
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../database/migrations/create_reviews_table.php.stub' =>
                        $this->migrationPath('create_reviews_table'),
                    __DIR__ . '/../database/migrations/create_ratings_table.php.stub' =>
                        $this->migrationPath('create_ratings_table', 1),
                ], 'migrations'
            );

            // Review images are opt-in and have their own publish tag so
            // applications that do not need them never create the table.
            $this->publishes(
                [
                    __DIR__ . '/../database/migrations/create_review_images_table.php.stub' =>
                        $this->migrationPath('create_review_images_table', 2),
                ], 'review-images-migrations'
            );

            // Publish the config file.
            $this->publishes(
                [
                    __DIR__ . '/../config/review-rateable.php' => config_path('review-rateable.php'),
                ], 'config'
            );
        }
    }

    /**
     * Reuse an already published migration filename instead of creating a
     * second timestamped copy when vendor:publish is run again.
     */
    protected function migrationPath(string $migrationName, int $timestampOffset = 0): string
    {
        $existingPaths = glob(database_path("migrations/*_{$migrationName}.php"));

        if (is_array($existingPaths) && $existingPaths !== []) {
            sort($existingPaths);

            return $existingPaths[0];
        }

        $timestamp = date('Y_m_d_His', time() + $timestampOffset);

        return database_path("migrations/{$timestamp}_{$migrationName}.php");
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/review-rateable.php',
            'review-rateable'
        );

        $this->app->bind(
            ReviewRateableContract::class,
            function () {
                return new ReviewRateableService();
            }
        );
    }
}
