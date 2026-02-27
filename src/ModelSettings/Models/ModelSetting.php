<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\ModelSettings\Models;

use Illuminate\Database\Eloquent\Model;

class ModelSetting extends Model
{
    /** @var array  */
    protected $guarded = [];

    /**
     * @return string
     */
    public function getTable(): string
    {
        return config('settings.model.table', 'model_settings');
    }
}
