<?php

declare(strict_types=1);

namespace Microservices\Services\Shadows;

use Illuminate\Database\Eloquent\Model;
use Microservices\Contracts\Colocation;
use Microservices\Contracts\Shadows\Shadowed;
use Microservices\Models\ShadowModel;
use ReflectionClass;

/** Finds, among the services this process runs, the copies of a source table and its source model. */
class ShadowRegistry
{
    public function __construct(protected readonly Colocation $colocation) {}

    /** @return list<class-string<ShadowModel>> the concrete copies of $sourceTable kept here, or by $keeper alone */
    public function shadowsOf(string $sourceTable, ?string $keeper = null): array
    {
        return array_values(array_filter(
            $this->localShadows($keeper),
            static fn (string $shadow): bool => $shadow::sourceTable() === $sourceTable,
        ));
    }

    /** @return list<class-string<ShadowModel>> every concrete copy the local services keep, or $keeper alone */
    public function localShadows(?string $keeper = null): array
    {
        $shadows = [];

        foreach ($keeper === null ? $this->colocation->local() : [$keeper] as $service) {
            $shadows = [...$shadows, ...$this->kept($service)];
        }

        return $shadows;
    }

    /** The local source model of $sourceTable, if this process runs its owner (or $owner is it). */
    public function sourceOf(string $sourceTable, ?string $owner = null): ?Shadowed
    {
        foreach ($owner === null ? $this->colocation->local() : [$owner] as $service) {
            foreach ($this->sources($service) as $class) {
                /** @var Model&Shadowed $model */
                $model = new $class;

                if ($model->getTable() === $sourceTable) {
                    return $model;
                }
            }
        }

        return null;
    }

    /** @return list<class-string<ShadowModel>> */
    public function scanShadows(string $service): array
    {
        $shadows = [];

        foreach (microservices_classes_with(ShadowModel::class, $this->colocation->classPath($service)) as $class) {
            if (! (new ReflectionClass($class))->isAbstract()) {
                /** @var class-string<ShadowModel> $class */
                $shadows[] = $class;
            }
        }

        return $shadows;
    }

    /** @return list<class-string> */
    public function scanSources(string $service): array
    {
        return microservices_classes_with(Shadowed::class, $this->colocation->classPath($service));
    }

    /** @return list<class-string<ShadowModel>> */
    protected function kept(string $service): array
    {
        return $this->scanShadows($service);
    }

    /** @return list<class-string> */
    protected function sources(string $service): array
    {
        return $this->scanSources($service);
    }
}
