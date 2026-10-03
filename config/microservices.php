<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | This service and the others
    |--------------------------------------------------------------------------
    | `name` is this application's name among the services: the emitter of its
    | events and the first segment of its RPC routes. Every other service is
    | declared by name: `host` is where it answers, a URL or ['url' => …,
    | 'transport' => …]; `namespace` is where its contracts and its RpcService
    | live in this codebase, so a call knows which service it goes to.
    */

    'name' => env('MICROSERVICES_NAME'),

    'services' => [
        // 'billing' => ['host' => env('BILLING_HOST'), 'namespace' => 'Billing'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Calls between services
    |--------------------------------------------------------------------------
    | Named transports, like queue connections; a driver other than `http`
    | comes from RpcTransportManager::extend(). A service's host may name the
    | one its calls travel on.
    */

    'rpc' => [
        'transport' => env('MICROSERVICES_RPC_TRANSPORT', 'http'),

        'transports' => [
            'http' => ['driver' => 'http'],
        ],

        // Signs every call; the caller and the called process must share it, so it falls back to APP_KEY.
        'secret' => env('MICROSERVICES_RPC_SECRET', env('APP_KEY', '')),
        'signature_ttl' => 30,

        // The cache store of the RpcService answers, shared by every process: the caller keeps an
        // answer, the owner forgets it when it writes. Null is the default store.
        'cache' => env('MICROSERVICES_RPC_CACHE_STORE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    | Named streams, like queue connections: each has a driver and that
    | driver's options. An event travels on the stream its stream() method
    | names, or on the default one. Drivers: `redis` (Redis Streams), `queue`
    | (a Laravel queue connection, no Redis needed), `array` (in memory, for
    | tests), `null` (drops everything). Any other driver resolves through a
    | creator registered on the transport manager with extend().
    */

    'events' => [
        'stream' => env('MICROSERVICES_STREAM', 'default'),

        'streams' => [

            // Nothing is trimmed on write: microservices:events:trim drops only what every consumer group acknowledged.
            'default' => [
                'driver' => env('MICROSERVICES_STREAM_DRIVER', 'redis'),
                'outbox' => env('MICROSERVICES_STREAM_OUTBOX', false), // true: written with the business transaction, published by microservices:events:publish
                'connection' => env('MICROSERVICES_STREAM_CONNECTION'), // null: the driver's own default connection
                'key' => 'microservices:events', // the Redis stream every service writes to, or the queue prefix
                'block' => 5_000,           // read block window, ms
                'count' => 50,              // entries per read
                'claim_after' => 60_000,    // reclaim entries a dead consumer left pending, ms
                'on_failure' => 'block',    // block: a failed entry is retried before later ones; skip: later ones go on
            ],

            // 'jobs' => [
            //     'driver' => 'queue',
            //     'connection' => env('MICROSERVICES_QUEUE_CONNECTION'),
            //     'key' => 'microservices-jobs', // each service reads {key}-{service}
            //     'sleep' => 1,                  // seconds to wait when the queue is empty
            // ],

        ],

        // 'event.name' => [Handler::class, …]
        'listen' => [],

        // Marks (event, handler) in `event_consumptions` inside the handler's transaction, so a
        // redelivery is a no-op. Null turns it on exactly when delivery is at-least-once.
        'guard' => env('MICROSERVICES_STREAM_GUARD'),

        // Keys of Laravel's Context carried in the envelope headers and restored around each handler.
        'propagate' => [],
    ],

];
