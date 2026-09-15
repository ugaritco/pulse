<?php

namespace Ugarit\Pulse;

use Composer\InstalledVersions;
use Heritage\Auth\Events\Logout;
use Heritage\Contracts\Auth\Access\Gate;
use Heritage\Contracts\Console\Kernel as ConsoleKernel;
use Heritage\Contracts\Events\Dispatcher;
use Heritage\Contracts\Foundation\Application;
use Heritage\Contracts\Http\Kernel as HttpKernel;
use Heritage\Foundation\Console\AboutCommand;
use Heritage\Queue\Events\Looping;
use Heritage\Queue\Events\WorkerStopping;
use Heritage\Routing\Router;
use Heritage\Support\Facades\Route;
use Heritage\Support\ServiceProvider;
use Heritage\Support\Str;
use Heritage\View\Compilers\BladeCompiler;
use Heritage\View\Factory as ViewFactory;
use Ugarit\Octane\Events\RequestReceived;
use Ugarit\Octane\Events\TaskReceived;
use Ugarit\Octane\Events\TickReceived;
use Ugarit\Pulse\Contracts\Ingest;
use Ugarit\Pulse\Contracts\ResolvesUsers;
use Ugarit\Pulse\Contracts\Storage;
use Ugarit\Pulse\Ingests\NullIngest;
use Ugarit\Pulse\Ingests\RedisIngest;
use Ugarit\Pulse\Ingests\StorageIngest;
use Ugarit\Pulse\Storage\DatabaseStorage;
use Ugarit\Sentinel\Http\Middleware\SentinelMiddleware;
use Livewire\LivewireManager;
use RuntimeException;

/**
 * @internal
 */
class PulseServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pulse.php', 'pulse'
        );

        $this->app->singleton(Pulse::class);
        $this->app->bind(Storage::class, DatabaseStorage::class);
        $this->app->singletonIf(ResolvesUsers::class, Users::class);

        $this->registerIngest();
    }

    /**
     * Register the ingest implementation.
     */
    protected function registerIngest(): void
    {
        $this->app->bind(Ingest::class, fn (Application $app) => match ($app->make('config')->get('pulse.ingest.driver')) {
            'storage' => $app->make(StorageIngest::class),
            'redis' => $app->make(RedisIngest::class),
            null, 'null' => $app->make(NullIngest::class),
            default => throw new RuntimeException("Unknown ingest driver [{$app->make('config')->get('pulse.ingest.driver')}]."),
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->make('config')->get('pulse.enabled')) {
            $this->app->make(Pulse::class)->register($this->app->make('config')->get('pulse.recorders'));
            $this->listenForEvents();
        } else {
            $this->app->make(Pulse::class)->stopRecording();
        }

        Route::middlewareGroup('pulse', [
            SentinelMiddleware::class.':pulse',
            ...$this->app->make('config')->get('pulse.middleware', []),
        ]);

        $this->registerAuthorization();
        $this->registerRoutes();
        $this->registerComponents();
        $this->registerResources();
        $this->registerPublishing();
        $this->registerCommands();
    }

    /**
     * Register the package authorization.
     */
    protected function registerAuthorization(): void
    {
        $this->callAfterResolving(Gate::class, function (Gate $gate, Application $app) {
            $gate->define('viewPulse', fn ($user = null) => $app->environment('local'));
        });
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        $this->callAfterResolving('router', function (Router $router, Application $app) {
            if ($app->make(Pulse::class)->registersRoutes()) {
                $router->group([
                    'domain' => $app->make('config')->get('pulse.domain', null),
                    'prefix' => $app->make('config')->get('pulse.path'),
                    'middleware' => 'pulse',
                ], function (Router $router) {
                    $router->get('/', function (Pulse $pulse, ViewFactory $view) {
                        return $view->make('pulse::dashboard');
                    })->name('pulse');
                });
            }
        });
    }

    /**
     * Listen for the events that are relevant to the package.
     */
    protected function listenForEvents(): void
    {
        $this->app->booted(function () {
            $this->callAfterResolving(Dispatcher::class, function (Dispatcher $event, Application $app) {
                $event->listen(function (Logout $event) use ($app) {
                    if ($event->user === null) {
                        return;
                    }

                    $pulse = $app->make(Pulse::class);

                    $pulse->rescue(fn () => $pulse->rememberUser($event->user));
                });

                $event->listen([
                    Looping::class,
                    WorkerStopping::class,
                ], function () use ($app) {
                    $app->make(Pulse::class)->ingest();
                });
            });

            $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel, Application $app) {
                $kernel->whenRequestLifecycleIsLongerThan(-1, function () use ($app) { // @phpstan-ignore method.notFound
                    $app->make(Pulse::class)->ingest();
                });
            });

            $this->callAfterResolving(ConsoleKernel::class, function (ConsoleKernel $kernel, Application $app) {
                $kernel->whenCommandLifecycleIsLongerThan(-1, function () use ($app) { // @phpstan-ignore method.notFound
                    $app->make(Pulse::class)->ingest();
                });
            });
        });

        $this->callAfterResolving(Dispatcher::class, function (Dispatcher $event, Application $app) {
            $event->listen([
                RequestReceived::class, // @phpstan-ignore class.notFound
                TaskReceived::class, // @phpstan-ignore class.notFound
                TickReceived::class, // @phpstan-ignore class.notFound
            ], function ($event) {
                if ($event->sandbox->resolved(Pulse::class)) {
                    $event->sandbox->make(Pulse::class)->setContainer($event->sandbox);
                }
            });
        });
    }

    /**
     * Register the package's components.
     */
    protected function registerComponents(): void
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            $blade->anonymousComponentPath(__DIR__.'/../resources/views/components', 'pulse');
        });

        $this->callAfterResolving('livewire', function (LivewireManager $livewire, Application $app) {
            $middleware = collect($app->make('config')->get('pulse.middleware')) // @phpstan-ignore argument.templateType, argument.templateType
                ->map(fn ($middleware) => is_string($middleware)
                    ? Str::before($middleware, ':')
                    : $middleware)
                ->all();

            $livewire->addPersistentMiddleware($middleware);

            $livewire->component('pulse.cache', Livewire\Cache::class);
            $livewire->component('pulse.usage', Livewire\Usage::class);
            $livewire->component('pulse.queues', Livewire\Queues::class);
            $livewire->component('pulse.servers', Livewire\Servers::class);
            $livewire->component('pulse.slow-jobs', Livewire\SlowJobs::class);
            $livewire->component('pulse.exceptions', Livewire\Exceptions::class);
            $livewire->component('pulse.slow-requests', Livewire\SlowRequests::class);
            $livewire->component('pulse.slow-queries', Livewire\SlowQueries::class);
            $livewire->component('pulse.period-selector', Livewire\PeriodSelector::class);
            $livewire->component('pulse.slow-outgoing-requests', Livewire\SlowOutgoingRequests::class);
        });
    }

    /**
     * Register the package's resources.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pulse');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pulse.php' => config_path('pulse.php'),
            ], ['pulse', 'pulse-config']);

            $this->publishes([
                __DIR__.'/../resources/views/dashboard.blade.php' => resource_path('views/vendor/pulse/dashboard.blade.php'),
            ], ['pulse', 'pulse-dashboard']);

            $method = method_exists($this, 'publishesMigrations') ? 'publishesMigrations' : 'publishes';

            $this->{$method}([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], ['pulse', 'pulse-migrations']);
        }
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\WorkCommand::class,
                Commands\CheckCommand::class,
                Commands\RestartCommand::class,
                Commands\ClearCommand::class,
            ]);

            AboutCommand::add('Pulse', fn () => [
                'Version' => InstalledVersions::getPrettyVersion('ugarit/pulse'),
                'Enabled' => AboutCommand::format(config('pulse.enabled'), console: fn ($value) => $value ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF'),
            ]);

            if (method_exists($this, 'reloads')) {
                $this->reloads('pulse:restart', 'pulse');
            }
        }
    }
}
