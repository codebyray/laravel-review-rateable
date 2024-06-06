<?php

return [
    // Default rating types for reviews
    'default_rating_types' => [
        'overall',
        'customer_service',
        'quality',
        'friendly',
        'price',
    ],

    // Default departments if needed
    'default_departments' => [
        'Sales',
        'Service',
        'Parts',
    ],

    // Other configuration settings can be added here
    'default_approved' => false,
    'max_rating_value' => 10,
    'min_rating_value' => 1,
];
