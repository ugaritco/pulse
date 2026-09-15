<?php

namespace Ugarit\Pulse\Commands;

use Heritage\Console\Command;
use Heritage\Support\InteractsWithTime;
use Ugarit\Pulse\Support\CacheStoreResolver;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal
 */
#[AsCommand(name: 'pulse:restart')]
class RestartCommand extends Command
{
    use InteractsWithTime;

    /**
     * The command's signature.
     *
     * @var string
     */
    public $signature = 'pulse:restart';

    /**
     * The command's description.
     *
     * @var string
     */
    public $description = 'Restart any running "work" and "check" commands';

    /**
     * Handle the command.
     */
    public function handle(CacheStoreResolver $cache): void
    {
        $cache->store()->forever('ugarit:pulse:restart', $this->currentTime());

        $this->components->info('Broadcasting pulse restart signal.');
    }
}
