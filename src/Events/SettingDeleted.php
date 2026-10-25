<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Events;

use Illuminate\Database\Eloquent\Model;

final class SettingDeleted
{
    public function __construct(
        public string $key,
        public mixed $oldValue,
        public ?Model $model = null,
    ) {}
}
