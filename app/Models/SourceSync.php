<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceSync extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime', 'succeeded_at' => 'datetime', 'discovered_count' => 'integer'];
    }
}
