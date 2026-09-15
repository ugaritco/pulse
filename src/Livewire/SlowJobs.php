<?php

namespace Ugarit\Pulse\Livewire;

use Heritage\Contracts\Support\Renderable;
use Heritage\Support\Facades\Config;
use Heritage\Support\Facades\View;
use Ugarit\Pulse\Recorders\Concerns\Thresholds;
use Ugarit\Pulse\Recorders\SlowJobs as SlowJobsRecorder;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

/**
 * @internal
 */
#[Lazy]
class SlowJobs extends Card
{
    use Thresholds;

    /**
     * Ordering.
     *
     * @var 'slowest'|'count'
     */
    #[Url(as: 'slow-jobs')]
    public string $orderBy = 'slowest';

    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$slowJobs, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(
                'slow_job',
                ['max', 'count'],
                match ($this->orderBy) {
                    'count' => 'count',
                    default => 'max',
                },
            )->map(fn ($row) => (object) [
                'job' => $row->key,
                'slowest' => $row->max,
                'count' => $row->count,
                'threshold' => $this->threshold($row->key, SlowJobsRecorder::class),
            ]),
            $this->orderBy,
        );

        return View::make('pulse::livewire.slow-jobs', [
            'time' => $time,
            'runAt' => $runAt,
            'config' => Config::get('pulse.recorders.'.SlowJobsRecorder::class),
            'slowJobs' => $slowJobs,
        ]);
    }
}
