<?php

declare(strict_types=1);

namespace Microservices\Migrations;

use Illuminate\Database\Migrations\Migration;
use Microservices\Contracts\Colocation;

/** The migration of a copy: its table is named {keeper}_{source}. */
abstract class ShadowMigration extends Migration
{
    /** The service whose database the migration runs in, when a migrate command runs it for several. */
    public static string $keeper = '';

    /** The source table the copy mirrors. */
    abstract protected function source(): string;

    protected function table(): string
    {
        $keeper = static::$keeper !== '' ? static::$keeper : (string) app(Colocation::class)->current();

        return $keeper.'_'.$this->source();
    }
}
