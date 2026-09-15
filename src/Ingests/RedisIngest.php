<?php

namespace Ugarit\Pulse\Ingests;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Heritage\Contracts\Config\Repository;
use Heritage\Redis\RedisManager;
use Heritage\Support\Collection;
use Ugarit\Pulse\Contracts\Ingest;
use Ugarit\Pulse\Contracts\Storage;
use Ugarit\Pulse\Entry;
use Ugarit\Pulse\Support\RedisAdapter;
use Ugarit\Pulse\Value;

/**
 * @internal
 */
class RedisIngest implements Ingest
{
    /**
     * The redis stream.
     */
    protected string $stream = 'ugarit:pulse:ingest';

    /**
     * Create a new Redis Ingest instance.
     */
    public function __construct(
        protected RedisManager $redis,
        protected Repository $config,
    ) {
        //
    }

    /**
     * Ingest the items.
     *
     * @param  Collection<int, Entry|Value>  $items
     */
    public function ingest(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $this->connection()->pipeline(function (RedisAdapter $pipeline) use ($items) {
            $items->each(fn (Entry|Value $entry) => $pipeline->xadd($this->stream, [
                'data' => serialize($entry),
            ]));
        });
    }

    /**
     * Trim the ingest.
     */
    public function trim(): void
    {
        $keep = $this->config->get('pulse.ingest.trim.keep');

        $this->connection()->xtrim(
            $this->stream,
            is_int($keep) ? 'MAXLEN' : 'MINID',
            '~',
            is_int($keep)
                ? $keep
                : CarbonImmutable::now()->subMilliseconds(
                    (int) CarbonInterval::fromString($keep)->totalMilliseconds
                )->getTimestampMs(),
        );
    }

    /**
     * Digest the ingested items.
     */
    public function digest(Storage $storage): int
    {
        $total = 0;

        while (true) {
            $entries = collect($this->connection()->xrange(
                $this->stream,
                '-',
                '+',
                $chunk = $this->config->get('pulse.ingest.redis.chunk')
            ));

            if ($entries->isEmpty()) {
                return $total;
            }

            $keys = $entries->keys();

            $storage->store(
                $entries->map(fn (array $payload): Entry|Value => unserialize($payload['data'], ['allowed_classes' => [Entry::class, Value::class]]))->values()
            );

            $this->connection()->xdel($this->stream, $keys);

            if ($entries->count() < $chunk) {
                return $total + $entries->count();
            }

            $total = $total + $entries->count();
        }
    }

    /**
     * Resolve the redis connection.
     */
    protected function connection(): RedisAdapter
    {
        return new RedisAdapter($this->redis->connection(
            $this->config->get('pulse.ingest.redis.connection')
        ), $this->config);
    }
}
