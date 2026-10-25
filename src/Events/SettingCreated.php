<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Events;

use Illuminate\Database\Eloquent\Model;

final class SettingCreated
{
    public function __construct(
        public string $key,
        public mixed $newValue,
        public ?Model $model = null,
    ) {}
}
