<?php

declare(strict_types=1);

namespace Microservices\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Microservices\Console\Commands\AnnounceShadows;
use Microservices\Console\Commands\ConsumeEvents;
use Microservices\Console\Commands\ExportEvents;
use Microservices\Console\Commands\ImportEvents;
use Microservices\Console\Commands\MakeFoundation;
use Microservices\Console\Commands\PublishEvents;
use Microservices\Console\Commands\RepublishEvents;
use Microservices\Console\Commands\TrimEvents;
use Microservices\Console\Commands\WantShadows;
use Microservices\Contracts\Colocation;
use Microservices\Contracts\Rpc\RpcTransport;
use Microservices\Contracts\Stream\Bus;
use Microservices\Contracts\Stream\Transport;
use Microservices\Http\Controllers\RpcController;
use Microservices\Http\Middleware\VerifyRpcSignature;
use Microservices\Services\Rpc\LocalServices;
use Microservices\Services\Rpc\RpcServices;
use Microservices\Services\Rpc\RpcTransportManager;
use Microservices\Services\Shadows\ShadowRegistry;
use Microservices\Services\SingleService;
use Microservices\Services\Stream\Emitter;
use Microservices\Services\Stream\PayloadVersions;
use Microservices\Services\Stream\TransportManager;

final class MicroservicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/microservices.php', 'microservices');

        // If: a package running several services in one process binds its own, whichever provider registers first.
        $this->app->bindIf(Colocation::class, SingleService::class);
        $this->app->singletonIf(ShadowRegistry::class);
        $this->app->bindIf(RpcTransport::class, RpcTransportManager::class);

        $this->app->singleton(TransportManager::class);
        $this->app->singleton(PayloadVersions::class);
        $this->app->singleton(RpcTransportManager::class);
        $this->app->singleton(RpcServices::class);
        $this->app->singleton(LocalServices::class);

        $this->app->singleton(Bus::class, Emitter::class);

        // Injecting the contract yields the default stream; the manager stays the seam
        // where a consumer registers its own driver with extend().
        $this->app->bind(
            Transport::class,
            static fn (Application $app): Transport => $app->make(TransportManager::class)->stream(),
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/microservices.php' => config_path('microservices.php'),
        ], 'microservices-config');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // The called side of HttpRpcTransport, one endpoint per local service: nothing unsigned reaches it.
        $this->app->make(Router::class)->pushMiddlewareToGroup('rpc', VerifyRpcSignature::class);

        foreach ($this->app->make(Colocation::class)->local() as $service) {
            Route::post("{$service}/rpc/{method}", RpcController::class)
                ->middleware('rpc')
                ->defaults('service', $service)
                ->name("microservices.rpc.{$service}");
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishEvents::class, RepublishEvents::class, ConsumeEvents::class, TrimEvents::class,
                ExportEvents::class, ImportEvents::class, AnnounceShadows::class, WantShadows::class, MakeFoundation::class,
            ]);
        }
    }
}
