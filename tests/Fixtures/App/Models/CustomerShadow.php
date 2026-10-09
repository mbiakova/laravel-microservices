<?php

declare(strict_types=1);

namespace Microservices\Tests\Fixtures\App\Models;

use Microservices\Models\ShadowModel;

/** Billing's customers, as orders keeps them: the table customers. */
final class CustomerShadow extends ShadowModel
{
    public static function owner(): string
    {
        return 'billing';
    }

    public static function sourceTable(): string
    {
        return 'customers';
    }
}
