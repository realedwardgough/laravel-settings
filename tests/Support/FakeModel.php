<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Support;

use Egough\LaravelSettings\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;

class FakeModel extends Model
{
    use HasSettings;

    protected $guarded = [];

    public $timestamps = false;
}
