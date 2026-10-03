# Configuration

`php artisan vendor:publish --tag=microservices-config` publishes `config/microservices.php`. The
package reads it only through the classes in `Microservices\Config\`: `Services`, `Rpc`, `Streamer`,
`RedisStream` and `QueueStream`.

| Key | Default | |
|---|---|---|
| `name` | `env('MICROSERVICES_NAME')` | this application's name among the services: the emitter of its events, the first segment of its RPC routes |
| `services` | `[]` | `'billing' => ['host' => …, 'namespace' => …]`: every other service. `host` is a URL or `['url' => …, 'transport' => …]` |
| `rpc.transport` | `env('MICROSERVICES_RPC_TRANSPORT', 'http')` | transport used when a host doesn't name one |
| `rpc.transports` | `http` | named transports, each with a `driver` and its options |
| `rpc.secret` | `env('MICROSERVICES_RPC_SECRET', env('APP_KEY'))` | signs every call; must be the same in every service |
| `rpc.signature_ttl` | `30` | how long a signature stays valid, in seconds |
| `rpc.cache` | `env('MICROSERVICES_RPC_CACHE_STORE')` | the cache store of the RPC answers, shared by every service; `null` for the default one |
| `events.stream` | `env('MICROSERVICES_STREAM', 'default')` | stream used when an event's `stream()` returns `null` |
| `events.streams` | `default`, on `redis` | named streams, each with a `driver` and its options |
| `events.listen` | `[]` | `'event.name' => [Handler::class, ...]` |
| `events.guard` | `env('MICROSERVICES_STREAM_GUARD')` | `null` means automatic |
| `events.propagate` | `[]` | `Context` keys copied into the envelope headers and the RPC calls |

Stream options, by driver:

| Driver | Option | Default | |
|---|---|---|---|
| any | `outbox` | `false` | write to `event_publications` in the current transaction; the `default` stream reads `MICROSERVICES_STREAM_OUTBOX` |
| `redis` | `connection` | `default` | a `database.redis` connection; the `default` stream reads `MICROSERVICES_STREAM_CONNECTION` |
| | `key` | `microservices:{stream}` | the Redis stream every service writes to; the `default` stream uses `microservices:events` |
| | `block` | `5000` | how long a read waits, in ms |
| | `count` | `50` | entries per read |
| | `claim_after` | `60000` | after how long a dead consumer's pending entries are taken back, in ms |
| | `on_failure` | `block` | `block`: a failed entry is retried before any later one; `skip`: later entries go on |
| `queue` | `connection` | `null` | a `queue.connections` entry, `null` for the default one |
| | `key` | `microservices-{stream}` | queues are `{key}-{service}` |
| | `sleep` | `1` | seconds to wait when the queue is empty |

## Several services in one process

An application is one service. A package that runs several services in one process binds
`Microservices\Contracts\Colocation`, which answers what the package needs to know about them:

| Method | Returns |
|---|---|
| `local()` | the services this process runs: each gets its RPC route, and one is the default consumer and publisher |
| `serviceOf($class)` | the service a class belongs to: the emitter of an event, the owner of a handler, the service an `RpcService` calls |
| `current()`, `within($service, $callback)` | the service the running code belongs to, and running code as another one |
| `connection($service)` | the database connection of a service, for its outbox, its consumption marks and its copies |
| `classPath($service)` | the directory searched for its copies and their sources |

[laravel-distributable](https://github.com/mk-josias/laravel-distributable) binds it so that each module is a
service, with its own database.

## Source layout

```
src/
├── Providers/      MicroservicesServiceProvider · RpcServiceProvider
├── Config/         Services · Rpc · Streamer · RedisStream · QueueStream
├── Contracts/      Colocation · Rpc/RpcTransport · Shadows/Shadowed
│   └── Stream/     Bus · Transport · Handler · Versioned · Idempotent · RedeliversEnvelopes · TrimsStreams · TracksAcknowledgements
├── Data/           Envelope
├── Events/         Event · ShadowChanged · ShadowWanted
├── Handlers/       SyncShadows · AnnounceShadowSource
├── Http/           Controllers/RpcController · Middleware/VerifyRpcSignature
├── Models/         ShadowModel          Migrations/ ShadowMigration          Traits/ ShadowSource
├── Console/        LocalServicesOption · Commands/{Publish,Republish,Consume,Trim,Export,Import}Events · {Announce,Want}Shadows
├── Exceptions/     ServiceException · ConfigurationException
├── Testing/        InteractsWithServices
├── Services/
│   ├── SingleService.php
│   ├── Rpc/        RpcService · RpcServices · LocalServices · RpcSignature · RpcTransportManager
│   ├── Stream/     Emitter · Dispatcher · EnvelopeFactory · TransportManager · PayloadVersions · Outbox/{Writer, Relay, Archive}
│   └── Shadows/    ShadowRegistry
└── Transports/
    ├── Rpc/        HttpRpcTransport
    └── Stream/     RedisStreamTransport · QueueTransport · ArrayTransport · NullTransport
```
