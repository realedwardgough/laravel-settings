<?php

declare(strict_types=1);

namespace Egough\LaravelSettings\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read string getTable
 *
 * @mixin Model
 */
class Setting extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config(key: 'settings.table', default: parent::getTable());
    }
}
