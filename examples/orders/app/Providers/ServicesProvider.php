<?php

declare(strict_types=1);

namespace App\Providers;

use Foundation\Billing\Contracts\BillingService;
use Foundation\Billing\Services\BillingRpcService;
use Microservices\Providers\RpcServiceProvider;

final class ServicesProvider extends RpcServiceProvider
{
    protected array $rpc = [BillingService::class => BillingRpcService::class];
}
