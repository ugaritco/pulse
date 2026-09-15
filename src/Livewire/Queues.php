<?php

namespace Ugarit\Pulse\Livewire;

use Heritage\Contracts\Support\Renderable;
use Heritage\Support\Facades\Config;
use Heritage\Support\Facades\View;
use Heritage\Support\Str;
use Ugarit\Pulse\Recorders\Queues as QueuesRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Livewire;

/**
 * @internal
 */
#[Lazy]
class Queues extends Card
{
    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$queues, $time, $runAt] = $this->remember(fn () => $this->graph(
            ['queued', 'processing', 'processed', 'released', 'failed'],
            'count',
        ));

        if (Livewire::isLivewireRequest()) {
            $this->dispatch('queues-chart-update', queues: $queues);
        }

        return View::make('pulse::livewire.queues', [
            'queues' => $queues,
            'showConnection' => $queues->keys()->map(fn ($queue) => Str::before($queue, ':'))->unique()->count() > 1,
            'time' => $time,
            'runAt' => $runAt,
            'config' => Config::get('pulse.recorders.'.QueuesRecorder::class),
        ]);
    }
}
