<?php

declare(strict_types=1);

namespace Microservices\Services\Rpc;

use Closure;
use Illuminate\Contracts\Container\Container;
use Microservices\Config\Rpc;
use Microservices\Contracts\Rpc\RpcTransport;
use Microservices\Exceptions\ConfigurationException;
use Microservices\Transports\Rpc\HttpRpcTransport;

/**
 * Routes each call to the named transport of microservices.rpc.transports its service's host
 * names. extend('grpc', fn ($app, array $config, string $name) => …) registers any driver.
 */
final class RpcTransportManager implements RpcTransport
{
    /** @var array<string, Closure(Container, array<string, mixed>, string): RpcTransport> */
    private array $creators = [];

    /** @var array<string, RpcTransport> */
    private array $transports = [];

    public function __construct(private readonly Container $container) {}

    public function invoke(string $service, string $contract, string $method, array $arguments = []): mixed
    {
        return $this->transport($this->config()->getTransportOf($service))->invoke($service, $contract, $method, $arguments);
    }

    public function transport(?string $name = null): RpcTransport
    {
        $name ??= $this->config()->getDefaultTransport();

        return $this->transports[$name] ??= $this->create($name, $this->config()->getTransport($name));
    }

    /** From the running application: Octane serves each request from its own copy. */
    private function config(): Rpc
    {
        return \Illuminate\Container\Container::getInstance()->make(Rpc::class);
    }

    /** @param Closure(Container, array<string, mixed>, string): RpcTransport $creator */
    public function extend(string $driver, Closure $creator): static
    {
        $this->creators[$driver] = $creator;
        $this->transports = [];

        return $this;
    }

    /** @param array<string, mixed> $config */
    private function create(string $name, array $config): RpcTransport
    {
        $driver = (string) ($config['driver'] ?? '');

        if (isset($this->creators[$driver])) {
            return ($this->creators[$driver])($this->container, $config, $name);
        }

        return match ($driver) {
            'http' => $this->container->make(HttpRpcTransport::class),
            default => throw ConfigurationException::unknownRpcDriver($driver, $name),
        };
    }
}
