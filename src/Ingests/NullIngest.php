<?php

namespace Ugarit\Pulse\Ingests;

use Heritage\Support\Collection;
use Ugarit\Pulse\Contracts\Ingest;
use Ugarit\Pulse\Contracts\Storage;
use Ugarit\Pulse\Entry;

class NullIngest implements Ingest
{
    /**
     * Ingest the items.
     *
     * @param  Collection<int, Entry>  $items
     */
    public function ingest(Collection $items): void
    {
        //
    }

    /**
     * Digest the ingested items.
     */
    public function digest(Storage $storage): int
    {
        return 0;
    }

    /**
     * Trim the ingest.
     */
    public function trim(): void
    {
        //
    }
}
