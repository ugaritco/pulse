<?php

namespace Ugarit\Pulse\Support;

use Heritage\Cache\CacheManager;
use Heritage\Contracts\Cache\Repository as CacheRepository;
use Heritage\Contracts\Config\Repository as ConfigRepository;

class CacheStoreResolver
{
    /**
     * Create a new cache connection resolver instance.
     */
    public function __construct(
        protected CacheManager $cache,
        protected ConfigRepository $config,
    ) {
        //
    }

    /**
     * Get the cache connection.
     */
    public function store(): CacheRepository
    {
        return $this->cache->store($this->config->get('pulse.cache'));
    }
}
