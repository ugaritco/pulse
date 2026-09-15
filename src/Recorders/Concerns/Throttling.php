<?php

namespace Ugarit\Pulse\Recorders\Concerns;

use Carbon\CarbonImmutable;
use DateInterval;
use Heritage\Support\Facades\App;
use Heritage\Support\InteractsWithTime;
use Ugarit\Pulse\Events\IsolatedBeat;
use Ugarit\Pulse\Events\SharedBeat;
use Ugarit\Pulse\Support\CacheStoreResolver;

trait Throttling
{
    use InteractsWithTime;

    /**
     * Determine if the recorder is ready to record another snapshot.
     */
    protected function throttle(DateInterval|int $interval, SharedBeat|IsolatedBeat $event, callable $callback, ?string $key = null): void
    {
        $key ??= static::class;

        if ($event instanceof SharedBeat) {
            $key = $event->instance.":{$key}";
        }

        $cache = App::make(CacheStoreResolver::class);

        $key = 'ugarit:pulse:throttle:'.$key;

        $lastRunAt = $cache->store()->get($key);

        if ($lastRunAt !== null && CarbonImmutable::createFromTimestamp($lastRunAt)->addSeconds($this->secondsUntil($interval))->isFuture()) {
            return;
        }

        $callback($event);

        $cache->store()->put($key, $event->time->getTimestamp(), $interval);
    }
}
