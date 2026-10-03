# Laravel Microservices

Laravel Microservices lets Laravel applications work together as microservices: each one owns its
data, and they talk only through signed RPC calls on shared contracts, ordered events with an
outbox, and read-only copies of each other's rows. It depends on nothing but Laravel.

- **Calls through contracts.** A service calls another through an interface it shares with it. The
  call is a signed HTTP request; no route to write on either side.
- **Events that are not lost.** An event can be written in the same transaction as the data, then
  published in order. A redelivered event is handled once.
- **Copies of another service's rows.** A service keeps the columns it needs from another service's
  table in its own database, kept up to date by events.
- **Any broker, any language.** Redis Streams and Laravel queues are built in; another broker plugs
  in through one interface. The wire format is plain JSON, so a service in Node or Go can take part.

[laravel-distributable](https://github.com/mk-josias/laravel-distributable) builds on this package to run
several services as modules of one codebase. Whether a service is its own application or a module
is a deployment choice; the code that calls it, announces to it or copies its rows is the same.

## Installation

```bash
composer require mk-josias/laravel-microservices
php artisan vendor:publish --tag=microservices-config
php artisan migrate   # the outbox tables: event_publications, event_consumptions
```

Name the application, and declare the services it talks to:

```php
// config/microservices.php
'name' => env('MICROSERVICES_NAME'),   // orders

'services' => [
    'billing' => ['host' => env('BILLING_HOST'), 'namespace' => 'Billing'],
],
```

`host` is where a service answers RPC calls. `namespace` is where its contracts and its
`RpcService` live in this codebase, often a small package the two services share.

## Calling another service

The service that answers owns a contract. The caller gets an `RpcService` that implements it:

```php
// Billing\Contracts\BillingService, shared by orders and billing
interface BillingService
{
    public function invoiceFor(int $orderId): ?array;
}

// Billing\Services\BillingRpcService, on the caller's side
final class BillingRpcService extends \Microservices\Services\Rpc\RpcService implements BillingService
{
    public function invoiceFor(int $orderId): ?array
    {
        return $this->call('invoiceFor', ['orderId' => $orderId]);
    }
}

// app/Providers/ServicesProvider.php, in orders
final class ServicesProvider extends \Microservices\Providers\RpcServiceProvider
{
    protected array $rpc = [BillingService::class => BillingRpcService::class];         // the contracts orders calls
    protected array $services = [OrdersService::class => \App\Services\OrdersService::class];  // the ones it answers
}
```

`app(BillingService::class)->invoiceFor(7)` sends `POST {billing host}/billing/rpc/invoiceFor`,
signed with the shared secret. Billing answers on that route, which the package serves for every
contract in its `$services`: no route to write. See [RPC](docs/rpc.md).

## Announcing and handling events

```php
final class OrderPlaced extends \Microservices\Events\Event
{
    public function __construct(private readonly int $id) {}

    public function name(): string { return 'orders.order.placed'; }

    public function payload(): array { return ['id' => $this->id]; }
}

app(\Microservices\Contracts\Stream\Bus::class)->emit(new OrderPlaced($order->id));
```

The consuming service lists its handlers and runs a consumer:

```php
// config/microservices.php, in billing
'events' => ['listen' => ['orders.order.placed' => [\App\Handlers\OpenInvoice::class]]],
```

```bash
php artisan microservices:events:consume
```

See [Events](docs/events.md) for the outbox, versions, streams and transports.

## Keeping a copy of another service's rows

```php
// in billing: the source, announced on every write
final class Customer extends Model implements \Microservices\Contracts\Shadows\Shadowed
{
    use \Microservices\Traits\ShadowSource;

    protected array $shadowed = ['name'];
}

// in orders: the copy, in the orders_customers table
final class CustomerShadow extends \Microservices\Models\ShadowModel
{
    public static function owner(): string { return 'billing'; }

    public static function sourceTable(): string { return 'customers'; }
}
```

See [Copies](docs/shadows.md).

## Documentation

| Page | |
|---|---|
| [RPC](docs/rpc.md) | contracts, `RpcService`, signature, cache, transports |
| [Events](docs/events.md) | envelope, outbox, versions, streams, transports, consumers |
| [Copies](docs/shadows.md) | `ShadowSource`, `ShadowModel`, filling a new copy |
| [Services in other languages](docs/other-languages.md) | what a Node or Go service has to speak |
| [Configuration](docs/configuration.md) | every key, and how to run several services in one process |

## Extending

| To | Implement | Register with |
|---|---|---|
| carry events on Kafka, RabbitMQ, another package | `Contracts\Stream\Transport` | `TransportManager::extend()` |
| carry calls on gRPC, an existing REST API | `Contracts\Rpc\RpcTransport` | `RpcTransportManager::extend()` |
| run several services in one process | `Contracts\Colocation` | the container (laravel-distributable does) |

## License

MIT
