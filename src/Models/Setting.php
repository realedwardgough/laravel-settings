<?php

namespace Egough\LaravelSettings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config(key: 'settings.table', default: parent::getTable());
    }
}
