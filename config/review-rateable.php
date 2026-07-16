<?php

return [

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
