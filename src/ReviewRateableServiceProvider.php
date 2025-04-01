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
            $timestamp = date('Y_m_d_His', time());
            $timestampTwo = date('Y_m_d_His', time()+1);
            $this->publishes(
                [
                    __DIR__ . '/../database/migrations/create_reviews_table.php.stub' =>
                        database_path("migrations/{$timestamp}_create_reviews_table.php"),
                    __DIR__ . '/../database/migrations/create_ratings_table.php.stub' =>
                        database_path("migrations/{$timestampTwo}_create_ratings_table.php"),
                ], 'migrations'
            );

            // Publish the config file.
            $this->publishes(
                [
                    __DIR__ . '/../config/review-rateable.php' => config_path('review-rateable.php'),
                ], 'config'
            );
        }

        // Merge package configuration.
        $this->mergeConfigFrom(
            __DIR__.'/../config/review-rateable.php',
            'review-rateable'
        );
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ReviewRateableContract::class,
            function ($app) {
                return new ReviewRateableService();
            }
        );
    }
}
