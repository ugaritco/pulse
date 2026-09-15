<?php

namespace Ugarit\Pulse\Events;

use Carbon\CarbonImmutable;

class IsolatedBeat
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public CarbonImmutable $time,
    ) {
        //
    }
}
