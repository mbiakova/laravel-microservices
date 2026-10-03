<?php

declare(strict_types=1);

namespace Foundation\Billing\Services;

use Foundation\Billing\Contracts\BillingService;
use Microservices\Services\Rpc\RpcService;

final class BillingRpcService extends RpcService implements BillingService
{
    public function customerName(int $id): ?string
    {
        $name = $this->call('customerName', ['id' => $id]);

        return is_string($name) ? $name : null;
    }
}
