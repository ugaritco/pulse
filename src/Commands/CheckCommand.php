<?php

namespace Ugarit\Pulse\Commands;

use Carbon\CarbonImmutable;
use Heritage\Console\Command;
use Heritage\Contracts\Cache\LockProvider;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Support\Env;
use Heritage\Support\Sleep;
use Heritage\Support\Str;
use Ugarit\Pulse\Events\IsolatedBeat;
use Ugarit\Pulse\Events\SharedBeat;
use Ugarit\Pulse\Pulse;
use Ugarit\Pulse\Support\CacheStoreResolver;
use Ugarit\Telescope\Contracts\EntriesRepository;
use Ugarit\Telescope\Telescope;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal
 */
#[AsCommand(name: 'pulse:check')]
class CheckCommand extends Command
{
    /**
     * The command's signature.
     *
     * @var string
     */
    public $signature = 'pulse:check {--once : Take a single snapshot}';

    /**
     * The command's description.
     *
     * @var string
     */
    public $description = 'Take a snapshot of the current server\'s pulse';

    /**
     * Handle the command.
     */
    public function handle(
        Pulse $pulse,
        CacheStoreResolver $cache,
        Dispatcher $event,
    ): int {
        $isVapor = (bool) Env::get('VAPOR_SSM_PATH');

        $instance = $isVapor ? 'vapor' : Str::random();

        $lastRestart = $cache->store()->get('ugarit:pulse:restart');

        $lock = ($store = $cache->store()->getStore()) instanceof LockProvider
            ? $store->lock('ugarit:pulse:check', 1)
            : null;

        while (true) {
            if ($lastRestart !== $cache->store()->get('ugarit:pulse:restart')) {
                return self::SUCCESS;
            }

            $now = CarbonImmutable::now();

            if ($lock?->get()) {
                $event->dispatch(new IsolatedBeat($now));
            }

            $event->dispatch(new SharedBeat($now, $instance));

            $pulse->ingest();

            $this->ensureTelescopeEntriesAreCollected();

            if ($isVapor || $this->option('once')) {
                return self::SUCCESS;
            }

            Sleep::until($now->addSecond());
        }
    }

    /**
     * Schedule Telescope to store entries if enabled.
     */
    protected function ensureTelescopeEntriesAreCollected(): void
    {
        if ($this->ugarit->bound(EntriesRepository::class)) {
            Telescope::store($this->ugarit->make(EntriesRepository::class));
        }
    }
}
