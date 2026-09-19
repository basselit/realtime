<?php

/*
|--------------------------------------------------------------------------
| Realtime: LiveKit voice rooms, user-to-user calls, Ably channel naming
|--------------------------------------------------------------------------
| The product never carries media. It signs short-lived join tokens, keeps
| the call records, receives LiveKit's room webhooks and, when needed,
| drives the room API. Publish this file to override any of it:
|
|   php artisan vendor:publish --tag=realtime-config
|
| Config is cached on servers: `php artisan config:cache` after a change.
*/

return [

    // Guard the signed-in user is read from. Sanctum for every Codatsoft API so far.
    'guard' => env('REALTIME_GUARD', 'sanctum'),

    // Table the call records reference and the invite request validates against.
    'users_table' => 'users',

    'livekit' => [

        // WebSocket URL handed to the mobile client (wss://<project>.livekit.cloud).
        // The server API host is derived from it (wss → https).
        'url' => env('LIVEKIT_URL'),

        'api_key'    => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),

        // Lifetime of a join token, in seconds. It only gates the *join*; a
        // participant already in a room is not dropped when it expires.
        'token_ttl' => (int) env('LIVEKIT_TOKEN_TTL', 3600),

        // Name the agent worker registers with. Set → the agent room token carries
        // an explicit dispatch for it. Empty → the worker must run in automatic
        // dispatch mode and joins every new room, including calls between users.
        'agent_name' => env('LIVEKIT_AGENT_NAME', ''),

        'rooms' => [

            // Seconds an empty room lives before the server closes it.
            'agent_empty_timeout' => 60,
            'call_empty_timeout'  => 120,

            // A call is exactly two people; the agent room is one person plus the worker.
            'agent_max_participants' => 2,
            'call_max_participants'  => 2,

            // Seconds a ringing call waits for an answer before it counts as missed.
            'call_ring_timeout' => 45,
        ],
    ],

    'routes' => [

        // Where the user-facing routes are mounted: <prefix>/voice/token, <prefix>/voice/calls/...
        'prefix' => 'api',

        // Middleware for the user-facing routes. The webhook gets its own set below:
        // LiveKit calls it without a user session and proves its origin by signature.
        'middleware'         => ['api', 'auth:sanctum'],
        'webhook_middleware' => ['api'],
    ],

    'broadcasting' => [

        // Register the private per-user channel every product's events broadcast on.
        // Set to false when the product defines its own channel authorization.
        'register_user_channel' => true,

        // Channel pattern and the user attribute its parameter must equal.
        'user_channel'   => 'user.{userId}',
        'user_attribute' => 'web_id',
    ],

];
