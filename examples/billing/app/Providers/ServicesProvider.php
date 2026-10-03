<?php

declare(strict_types=1);

namespace App\Providers;

use Foundation\Billing\Contracts\BillingService;
use Microservices\Providers\RpcServiceProvider;

final class ServicesProvider extends RpcServiceProvider
{
    protected array $services = [BillingService::class => \App\Services\BillingService::class];
}
