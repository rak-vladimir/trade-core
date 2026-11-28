<?php

namespace App\Http\Resources\Api\V1\Stock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStockBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_id'     => $this['product_id'],
            'total_quantity' => $this['total_quantity'],
            'warehouses'     => $this['warehouses']->map(fn($stock) => [
                'id'       => $stock->warehouse->id,
                'code'     => $stock->warehouse->code,
                'name'     => $stock->warehouse->name,
                'quantity' => $stock->quantity,
            ]),
        ];
    }
}
