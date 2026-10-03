<?php

declare(strict_types=1);

namespace Foundation\Billing\Contracts;

interface BillingService
{
    public function customerName(int $id): ?string;
}
