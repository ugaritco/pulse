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
class SlowRequests
{
    use Concerns\Ignores,
        Concerns\LivewireRoutes,
        Concerns\Sampling,
        Concerns\Thresholds,
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
        if (! $request->route() instanceof Route || ! $this->shouldSample()) {
            return;
        }

        [$path, $via] = $this->resolveRoutePath($request);

        if (
            $this->shouldIgnore($path) ||
            $this->underThreshold($duration = ((int) $startedAt->diffInMilliseconds()), $path)
        ) {
            return;
        }

        $this->pulse->record(
            type: 'slow_request',
            key: json_encode([$request->method(), $path, $via], flags: JSON_THROW_ON_ERROR),
            value: $duration,
            timestamp: $startedAt,
        )->max()->count();

        if ($userId = $this->pulse->resolveAuthenticatedUserId()) {
            $this->pulse->record(
                type: 'slow_user_request',
                key: (string) $userId,
                timestamp: $startedAt,
            )->count();
        }
    }
}
