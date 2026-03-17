<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class FakeModel extends Model
{
    protected $guarded = [];

    public $timestamps = false;
}
