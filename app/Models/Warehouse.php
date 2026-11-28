<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'address',
    'is_active'
])]
class Warehouse extends Model
{
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_warehouse_id');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_warehouse_id');
    }
}
