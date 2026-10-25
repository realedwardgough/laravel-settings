<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Contracts;

interface DefersSettingEvents
{
    /** Run after the storage transaction commits, or immediately when none is active. */
    public function afterCommit(callable $callback): void;
}
