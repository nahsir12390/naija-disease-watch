<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sync_enabled' => 'boolean'];
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['name' => 'Naija Disease Watch', 'sync_enabled' => true]);
    }
}
