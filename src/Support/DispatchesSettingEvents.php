<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Support;

use Egough\LaravelSettings\Contracts\DefersSettingEvents;

trait DispatchesSettingEvents
{
    private function dispatchSettingEvent(object $event): void
    {
        $dispatch = function () use ($event): void {
            $this->clearCache();
            ($this->events ?? app('events'))->dispatch($event);
        };

        if ($this->repo instanceof DefersSettingEvents) {
            $this->repo->afterCommit($dispatch);
        } else {
            $dispatch();
        }
    }
}
