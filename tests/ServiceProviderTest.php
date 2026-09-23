<?php

namespace Codebyray\ReviewRateable\Tests;

use Codebyray\ReviewRateable\ReviewRateableServiceProvider;
use Illuminate\Filesystem\Filesystem;

it('reuses an existing migration filename when migrations are published again', function () {
    $originalDatabasePath = $this->app->databasePath();
    $temporaryDatabasePath = sys_get_temp_dir().'/review-rateable-'.uniqid('', true);
    $migrationsPath = $temporaryDatabasePath.'/migrations';
    $filesystem = new Filesystem;

    $filesystem->makeDirectory($migrationsPath, 0755, true);
    $existingMigration = $migrationsPath.'/2026_01_02_030405_create_reviews_table.php';
    $filesystem->put($existingMigration, '<?php');

    $provider = new class($this->app) extends ReviewRateableServiceProvider
    {
        public function migrationPathForTest(string $migrationName, int $timestampOffset = 0): string
        {
            return $this->migrationPath($migrationName, $timestampOffset);
        }
    };

    try {
        $this->app->useDatabasePath($temporaryDatabasePath);

        expect($provider->migrationPathForTest('create_reviews_table'))->toBe($existingMigration)
            ->and($provider->migrationPathForTest('create_ratings_table'))
            ->toMatch('/\/migrations\/\d{4}_\d{2}_\d{2}_\d{6}_create_ratings_table\.php$/');
    } finally {
        $this->app->useDatabasePath($originalDatabasePath);
        $filesystem->deleteDirectory($temporaryDatabasePath);
    }
});
