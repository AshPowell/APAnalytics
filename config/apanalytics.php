<?php

return [
    'db_connection' => env('APANALYTICS_DB_CONNECTION', 'mongodb'),

    'namespace' => env('APANALYTICS_NAMESPACE'),

    /*
     * Tables that should be formatted using base class
     */
    'format_collections' => [
        'views',
        'impressions',
        'claims',
    ],

    /*
     * Check for ownership using isOwner() on these models before returning analytic results
     */
    'models_require_ownership' => [
        'offer',
        'animal',
        'event',
    ],
];
