<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'symbol',
    'decimal_places',
    'is_active'
])]
class Currency extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active'      => 'boolean',
        ];
    }
}
