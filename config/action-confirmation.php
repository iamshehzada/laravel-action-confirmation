<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Header name used in API mode
    |--------------------------------------------------------------------------
    */
    'api_header' => 'X-Confirmation-Token',

    /*
    |--------------------------------------------------------------------------
    | Actions configuration
    |--------------------------------------------------------------------------
    | Each action can define:
    | - target: Required model class
    | - ttl: seconds
    | - channels: ['web','api']
    | - reason_required: bool
    */
    'actions' => [
        'delete_user' => [
            'target' => App\Models\User::class,
            'ttl' => 300,
            'channels' => ['api', 'web'],
            'reason_required' => true,
        ],
    ],
];
