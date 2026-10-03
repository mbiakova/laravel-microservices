<?php

declare(strict_types=1);

namespace Microservices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Microservices\Contracts\Colocation;
use Microservices\Exceptions\ServiceException;

/**
 * A local, read-only copy of another service's rows: same key as the source row, soft-deleted
 * when the source is, written only through sync(). Each service keeping a copy declares it once,
 * so the copy is a table of the keeper's database, named {keeper}_{source table}.
 *
 * @phpstan-consistent-constructor
 */
abstract class ShadowModel extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    private bool $syncing = false;

    /** The service owning the source table, e.g. `iam`. */
    abstract public static function owner(): string;

    /** The source table this copy mirrors, e.g. `iam_users`. */
    abstract public static function sourceTable(): string;

    /** The service keeping this copy: the one its class belongs to. */
    public static function keeper(): string
    {
        return app(Colocation::class)->serviceOf(static::class)
            ?? throw ServiceException::outsideService(static::class);
    }

    /** Written by a package handler that serves every keeper at once: the keeper is pinned here, not by the context. */
    public function getConnectionName(): ?string
    {
        return app(Colocation::class)->connection(static::keeper());
    }

    public function getTable(): string
    {
        return static::keeper().'_'.static::sourceTable();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(static fn (self $model): bool => $model->syncing);
        static::deleting(static fn (self $model): bool => $model->syncing);
    }

    /** @param array<string, mixed> $attributes */
    public static function sync(int|string $key, array $attributes): void
    {
        $shadow = static::query()->withoutGlobalScopes()->find($key) ?? new static;
        $shadow->syncing = true;

        $shadow->forceFill([...static::beforeSync($attributes, $shadow->exists ? $shadow : null), $shadow->getKeyName() => $key])->save();

        $shadow->syncing = false;
    }

    /**
     * Derives what the copy needs and the source never announced; $previous is the copy as it
     * stood, so a transition can be dated.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function beforeSync(array $attributes, ?self $previous = null): array
    {
        return $attributes;
    }
}
