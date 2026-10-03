<?php

declare(strict_types=1);

namespace Microservices\Console\Commands;

use Illuminate\Console\Command;
use Microservices\Contracts\Stream\Bus;
use Microservices\Events\ShadowWanted;
use Microservices\Services\Shadows\ShadowRegistry;

/** Run by a service keeping copies: asks the owner of each source table to announce what it holds. */
final class WantShadows extends Command
{
    protected $signature = 'microservices:shadows:want
        {--keepers=* : Only these services ask for their copies (default: every local keeper)}
        {--sources=* : Only these source tables (default: every one they copy)}';

    protected $description = 'Ask the owners of the source tables copied here to announce their rows.';

    public function handle(ShadowRegistry $catalog, Bus $bus): int
    {
        $keepers = (array) $this->option('keepers');
        $sources = (array) $this->option('sources');

        foreach ($catalog->localShadows() as $shadow) {
            $keeper = $shadow::keeper();

            if (($keepers !== [] && ! in_array($keeper, $keepers, true))
                || ($sources !== [] && ! in_array($shadow::sourceTable(), $sources, true))) {
                continue;
            }

            $bus->emit(new ShadowWanted($keeper, $shadow::owner(), $shadow::sourceTable()));
            $this->line("→ {$keeper} wants {$shadow::sourceTable()}");
        }

        return self::SUCCESS;
    }
}
