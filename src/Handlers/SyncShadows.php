<?php

declare(strict_types=1);

namespace Microservices\Handlers;

use Microservices\Contracts\Colocation;
use Microservices\Contracts\Stream\Handler;
use Microservices\Contracts\Stream\Idempotent;
use Microservices\Services\Shadows\ShadowRegistry;

/** Writes an announced source row into the copies of the consuming service, or of every local one. */
final readonly class SyncShadows implements Handler, Idempotent
{
    public function __construct(
        private ShadowRegistry $catalog,
        private Colocation $colocation,
    ) {}

    public function handle(string $name, array $payload): void
    {
        /** @var array<string, mixed> $attributes */
        $attributes = (array) ($payload['attributes'] ?? []);
        $key = $payload['key'];

        foreach ($this->catalog->shadowsOf((string) $payload['source'], $this->colocation->current()) as $shadow) {
            $shadow::sync(is_int($key) ? $key : (string) $key, $attributes);
        }
    }
}
