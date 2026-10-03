<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Microservices\Contracts\Shadows\Shadowed;
use Microservices\Traits\ShadowSource;

/**
 * @property int $id
 * @property string $name
 */
final class Customer extends Model implements Shadowed
{
    use ShadowSource;

    protected $fillable = ['name'];

    /** @var list<string> */
    protected array $shadowed = ['name'];
}
