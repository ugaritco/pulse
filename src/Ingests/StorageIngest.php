<?php

namespace Ugarit\Pulse\Ingests;

use Heritage\Support\Collection;
use Ugarit\Pulse\Contracts\Ingest;
use Ugarit\Pulse\Contracts\Storage;
use Ugarit\Pulse\Entry;

/**
 * @internal
 */
class StorageIngest implements Ingest
{
    /**
     * Create a new Storage Ingest instance.
     */
    public function __construct(protected Storage $storage)
    {
        //
    }

    /**
     * Ingest the items.
     *
     * @param  Collection<int, Entry>  $items
     */
    public function ingest(Collection $items): void
    {
        $this->storage->store($items);
    }

    /**
     * Trim the ingest.
     */
    public function trim(): void
    {
        $this->storage->trim();
    }

    /**
     * Digest the ingested items.
     */
    public function digest(Storage $storage): int
    {
        return 0;
    }
}
