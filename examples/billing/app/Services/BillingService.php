<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Foundation\Billing\Contracts\BillingService as Contract;

final class BillingService implements Contract
{
    public function customerName(int $id): ?string
    {
        return Customer::query()->find($id)?->name;
    }
}
