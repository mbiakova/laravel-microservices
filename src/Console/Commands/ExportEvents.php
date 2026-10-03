<?php

declare(strict_types=1);

namespace Microservices\Console\Commands;

use Illuminate\Console\Command;
use Microservices\Console\LocalServicesOption;
use Microservices\Services\Stream\Outbox\Archive;
use SplFileObject;

/** Keeps the outbox small without losing the journal: published rows move to a file. */
final class ExportEvents extends Command
{
    use LocalServicesOption;

    protected $signature = 'microservices:events:export
        {path : The JSON-lines file to append to}
        {--service=* : Limit to these services}
        {--stream= : Only rows of this stream}
        {--until= : Only rows emitted before this date}
        {--where=* : Only rows matching column=value, or payload.field=value}
        {--acknowledged : Only rows every consumer has acknowledged}
        {--batch=1000 : Rows read and deleted per batch}';

    protected $description = 'Move published outbox rows to a file, then delete them from the outbox.';

    public function handle(Archive $archive): int
    {
        $file = new SplFileObject((string) $this->argument('path'), 'a');
        $until = $this->option('until');
        $stream = $this->option('stream');
        $where = [];

        foreach ((array) $this->option('where') as $filter) {
            [$column, $value] = array_pad(explode('=', (string) $filter, 2), 2, '');
            $where[$column] = $value;
        }

        foreach ($this->selectedServices() as $service) {
            $count = $archive->export(
                service: $service,
                file: $file,
                until: is_string($until) ? $until : null,
                stream: is_string($stream) ? $stream : null,
                where: $where,
                acknowledged: (bool) $this->option('acknowledged'),
                batch: max(1, (int) $this->option('batch')),
            );

            $this->line("→ {$service}: {$count} exported");
        }

        return self::SUCCESS;
    }
}
