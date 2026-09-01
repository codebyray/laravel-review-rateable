<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Define the model class that represents your users. This is used for the
    | relationship between reviews and the user who posted them.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Rating Value Boundaries
    |--------------------------------------------------------------------------
    |
    | These settings define the maximum and minimum allowed rating values.
    | You can adjust these values as needed.
    |
    */
    'max_rating_value' => 5,
    'min_rating_value' => 1,

    /*
    |--------------------------------------------------------------------------
    | Review Approval Default
    |--------------------------------------------------------------------------
    |
    | This value determines whether a new review is automatically approved
    | or requires manual approval.
    |
    */
    'approved_review' => false,

    /*
    |--------------------------------------------------------------------------
    | Optional Review Images
    |--------------------------------------------------------------------------
    |
    | Review images are enabled by publishing and running the migration tagged
    | "review-images-migrations". Files are stored beneath a directory named
    | for the review ID. Thumbnail files may be supplied by the application;
    | when omitted, thumbnail_url falls back to the original image URL.
    |
    */
    'images' => [
        'disk' => env('REVIEW_IMAGE_DISK', env('FILESYSTEM_DISK', 'public')),
        'directory' => 'review-images',
        'thumbnail_directory' => 'review-images/thumbnails',
        'max_count' => 10,
        'max_file_size' => 5120, // Kilobytes
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ],
        'delete_files_on_delete' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Departments & Their Rating Labels
    |--------------------------------------------------------------------------
    |
    | You can define multiple departments. Each department has its own set of
    | rating keys and display labels. When adding a review, you'll pass the
    | department name and only the ratings for that department will be processed.
    |
    */
    'departments' => [
        'default' => [
            'ratings' => [
                'overall' => 'Overall Rating',
                'customer_service' => 'Customer Service Rating',
                'quality' => 'Quality Rating',
                'price' => 'Price Rating',
            ],
        ],
        'sales' => [
            'ratings' => [
                'overall' => 'Overall Rating',
                'communication' => 'Communication Rating',
                'follow_up' => 'Follow-Up Rating',
                'price' => 'Price Rating',
            ],
        ],
        'support' => [
            'ratings' => [
                'overall' => 'Overall Rating',
                'speed' => 'Response Speed',
                'knowledge' => 'Knowledge Rating',
            ],
        ],
    ],
];
