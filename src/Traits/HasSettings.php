<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Traits;

use Egough\LaravelSettings\ModelSettings\ModelSettingsManager;

trait HasSettings
{
    /**
     * @return ModelSettingsManager
     */
    public function settings(): ModelSettingsManager
    {
        return app(ModelSettingsManager::class)->for($this);
    }

    /**
     * @return ModelSettingsManager
     */
    public function setting(): ModelSettingsManager
    {
        return $this->settings();
    }
}
