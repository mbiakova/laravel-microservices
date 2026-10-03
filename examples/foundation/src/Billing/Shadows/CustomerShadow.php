<?php

declare(strict_types=1);

namespace Foundation\Billing\Shadows;

use Microservices\Models\ShadowModel;

/**
 * @property int $id
 * @property string $name
 */
abstract class CustomerShadow extends ShadowModel
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
