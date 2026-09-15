<?php

namespace Ugarit\Pulse\Recorders;

use Heritage\Contracts\Foundation\Application;
use Heritage\Contracts\Http\Kernel;
use Heritage\Http\Request;
use Heritage\Routing\Route;
use Heritage\Support\Carbon;
use Ugarit\Pulse\Concerns\ConfiguresAfterResolving;
use Ugarit\Pulse\Pulse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class UserRequests
{
    use Concerns\Ignores,
        Concerns\LivewireRoutes,
        Concerns\Sampling,
        ConfiguresAfterResolving;

    /**
     * Create a new recorder instance.
     */
    public function __construct(
        protected Pulse $pulse,
    ) {
        //
    }

    /**
     * Register the recorder.
     */
    public function register(callable $record, Application $app): void
    {
        $this->afterResolving(
            $app,
            Kernel::class,
            fn (Kernel $kernel) => $kernel->whenRequestLifecycleIsLongerThan(-1, $record) // @phpstan-ignore method.notFound
        );
    }

    /**
     * Record the request.
     */
    public function record(Carbon $startedAt, Request $request, Response $response): void
    {
        if (
            ($userId = $this->pulse->resolveAuthenticatedUserId()) === null ||
            ! $request->route() instanceof Route ||
            ! $this->shouldSample()
        ) {
            return;
        }

        if ($this->shouldIgnore($this->resolveRoutePath($request)[0])) {
            return;
        }

        $this->pulse->record(
            type: 'user_request',
            key: (string) $userId,
            timestamp: $startedAt->getTimestamp()
        )->count();
    }
}
