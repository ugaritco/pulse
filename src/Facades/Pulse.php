<?php

namespace Ugarit\Pulse\Facades;

use Heritage\Support\Facades\Facade;

/**
 * @method static \Ugarit\Pulse\Pulse register(array $recorders)
 * @method static \Ugarit\Pulse\Entry record(\UnitEnum|string $type, \UnitEnum|string $key, int|null $value = null, \DateTimeInterface|int|null $timestamp = null)
 * @method static \Ugarit\Pulse\Value set(\UnitEnum|string $type, \UnitEnum|string $key, string $value, \DateTimeInterface|int|null $timestamp = null)
 * @method static \Ugarit\Pulse\Pulse lazy(callable $closure)
 * @method static \Ugarit\Pulse\Pulse report(\Throwable $e)
 * @method static \Ugarit\Pulse\Pulse startRecording()
 * @method static \Ugarit\Pulse\Pulse stopRecording()
 * @method static mixed ignore(callable $callback)
 * @method static \Ugarit\Pulse\Pulse flush()
 * @method static \Ugarit\Pulse\Pulse filter(callable $filter)
 * @method static int ingest()
 * @method static int digest()
 * @method static bool wantsIngesting()
 * @method static \Heritage\Support\Collection recorders()
 * @method static \Ugarit\Pulse\Contracts\ResolvesUsers resolveUsers(\Heritage\Support\Collection $keys)
 * @method static \Ugarit\Pulse\Pulse user(callable $callback)
 * @method static callable authenticatedUserIdResolver()
 * @method static string|int|null resolveAuthenticatedUserId()
 * @method static \Ugarit\Pulse\Pulse rememberUser(\Heritage\Contracts\Auth\Authenticatable $user)
 * @method static \Ugarit\Pulse\Pulse|string css(string|\Heritage\Contracts\Support\Htmlable|array|null $css = null)
 * @method static string js()
 * @method static array defaultVendorCacheKeys()
 * @method static bool registersRoutes()
 * @method static \Ugarit\Pulse\Pulse ignoreRoutes()
 * @method static \Ugarit\Pulse\Pulse handleExceptionsUsing(callable $callback)
 * @method static mixed|null rescue(callable $callback)
 * @method static \Ugarit\Pulse\Pulse setContainer(\Heritage\Contracts\Foundation\Application $container)
 * @method static void afterResolving(\Heritage\Contracts\Foundation\Application $app, string $class, \Closure $callback)
 * @method static void store(\Heritage\Support\Collection $items)
 * @method static void trim()
 * @method static void purge(array $types = null)
 * @method static \Heritage\Support\Collection values(string $type, array $keys = null)
 * @method static \Heritage\Support\Collection graph(array $types, string $aggregate, \Carbon\CarbonInterval $interval)
 * @method static \Heritage\Support\Collection aggregate(string $type, string|array $aggregates, \Carbon\CarbonInterval $interval, string|null $orderBy = null, string $direction = 'desc', int $limit = 101)
 * @method static \Heritage\Support\Collection aggregateTypes(string|array $types, string $aggregate, \Carbon\CarbonInterval $interval, string|null $orderBy = null, string $direction = 'desc', int $limit = 101)
 * @method static float|\Heritage\Support\Collection aggregateTotal(string|array $types, string $aggregate, \Carbon\CarbonInterval $interval)
 *
 * @see \Ugarit\Pulse\Pulse
 */
class Pulse extends Facade
{
    /**
     * Get the registered name of the component.
     */
    public static function getFacadeAccessor(): string
    {
        return \Ugarit\Pulse\Pulse::class;
    }
}
